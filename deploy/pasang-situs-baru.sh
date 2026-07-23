#!/usr/bin/env bash
# =============================================================================
# pasang-situs-baru.sh — Pasang SATU instalasi DAYA AI di satu domain
#
# BUKAN multi-tenant. Setiap domain = instalasi terpisah penuh:
#   - direktori sendiri  : /var/www/<domain>
#   - database sendiri   : dibuat otomatis (MySQL/MariaDB)
#   - .env sendiri       : APP_NAME unik (WAJIB — jadi prefix cache/queue Redis,
#                          mencegah tabrakan antar situs di satu VPS)
#   - nginx vhost sendiri, worker queue (supervisor) sendiri
#   - kunci IndexNow sendiri (WAJIB unik per domain)
#
# Pemakaian (jalankan sebagai root di VPS Ubuntu/Debian):
#   sudo bash pasang-situs-baru.sh herbalku.com "HerbalKu"
#
# Kunci AI opsional — bisa dibagi antar situs (satu akun OpenRouter):
#   sudo AI_API_KEY="sk-or-v1-xxx" bash pasang-situs-baru.sh herbalku.com "HerbalKu"
#
# Prasyarat sekali per VPS: nginx, php-fpm (>=8.2 + ext mbstring xml curl zip
# mysql redis), composer, mysql/mariadb, redis, supervisor, certbot.
# Source code master di: $SOURCE_DIR (default /opt/daya-engine).
# =============================================================================
set -euo pipefail

DOMAIN="${1:?Pemakaian: pasang-situs-baru.sh <domain> <NamaBrand>}"
BRAND="${2:?Pemakaian: pasang-situs-baru.sh <domain> <NamaBrand>}"

SOURCE_DIR="${SOURCE_DIR:-/opt/daya-engine}"
WEB_ROOT="/var/www/${DOMAIN}"
PHP_SOCK="${PHP_SOCK:-/run/php/php8.4-fpm.sock}"

# Kunci AI (opsional, boleh dibagi antar situs). Kosong = fitur AI nonaktif.
AI_DRIVER="${AI_DRIVER:-openrouter}"
AI_MODEL="${AI_MODEL:-anthropic/claude-sonnet-5}"
AI_API_KEY="${AI_API_KEY:-}"

SLUG="$(echo "${DOMAIN}" | tr '.-' '__' | tr -cd 'a-zA-Z0-9_' | cut -c1-32)"
DB_NAME="daya_${SLUG}"
DB_USER="daya_${SLUG}"
DB_PASS="$(openssl rand -base64 24 | tr -d '/+=' | cut -c1-24)"
INDEXNOW_KEY="$(openssl rand -hex 16)"   # WAJIB unik per domain

echo "==> Memasang situs mandiri: ${DOMAIN} (brand: ${BRAND})"
[ -d "${SOURCE_DIR}" ] || { echo "Source code tidak ditemukan di ${SOURCE_DIR}. Set SOURCE_DIR=... atau salin dulu."; exit 1; }
[ -d "${WEB_ROOT}" ] && { echo "${WEB_ROOT} sudah ada — batal agar tidak menimpa."; exit 1; }

echo "==> [1/8] Menyalin source code (instalasi terpisah)"
mkdir -p "${WEB_ROOT}"
rsync -a --exclude vendor --exclude node_modules --exclude .git \
      --exclude storage/logs --exclude storage/framework/cache \
      "${SOURCE_DIR}/" "${WEB_ROOT}/"
mkdir -p "${WEB_ROOT}"/storage/{logs,app/private,app/brain,framework/{cache/data,sessions,views}}

echo "==> [2/8] Menyalin otak AI (knowledge Markdown)"
if [ -d "${WEB_ROOT}/database/data/brain" ]; then
    cp -n "${WEB_ROOT}/database/data/brain/"*.md "${WEB_ROOT}/storage/app/brain/" 2>/dev/null || true
    echo "    $(ls -1 "${WEB_ROOT}/storage/app/brain/" 2>/dev/null | wc -l) file pengetahuan terpasang"
fi

echo "==> [3/8] Membuat database & user khusus situs ini"
mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;"

echo "==> [4/8] Menulis .env khusus situs ini"
cp "${WEB_ROOT}/.env.example" "${WEB_ROOT}/.env"
env_set() { sed -i "s|^#\?\s*$1=.*|$1=$2|" "${WEB_ROOT}/.env" || true; grep -q "^$1=" "${WEB_ROOT}/.env" || echo "$1=$2" >> "${WEB_ROOT}/.env"; }
env_set APP_NAME "\"${BRAND}\""                 # WAJIB unik → prefix cache/queue Redis
env_set APP_ENV production
env_set APP_DEBUG false
env_set APP_URL "https://${DOMAIN}"
env_set DAYA_ENGINE_NAME "\"DAYA AI\""
env_set DB_CONNECTION mysql
env_set DB_DATABASE "${DB_NAME}"
env_set DB_USERNAME "${DB_USER}"
env_set DB_PASSWORD "${DB_PASS}"
env_set CACHE_STORE redis
env_set QUEUE_CONNECTION redis
env_set SESSION_DRIVER redis
env_set DAYA_PAGE_CACHE_TTL 900
env_set DAYA_CATEGORY_CACHE_TTL 600
env_set INDEXNOW_KEY "${INDEXNOW_KEY}"
env_set AI_DRIVER "${AI_DRIVER}"
env_set AI_MODEL "\"${AI_MODEL}\""
[ -n "${AI_API_KEY}" ] && env_set AI_API_KEY "${AI_API_KEY}"

ADMIN_EMAIL="admin@${DOMAIN}"
ADMIN_PASS="$(openssl rand -base64 24 | tr -d '/+=' | cut -c1-16)"
env_set ADMIN_EMAIL "${ADMIN_EMAIL}"
env_set ADMIN_PASSWORD "${ADMIN_PASS}"

echo "==> [5/8] Composer + key + migrate (TANPA data demo)"
cd "${WEB_ROOT}"
composer install --no-dev --optimize-autoloader --no-interaction --quiet
php artisan key:generate --force -q
php artisan migrate --force -q
php artisan db:seed --force -q          # hanya akun admin — data demo TIDAK ikut
php artisan config:cache -q && php artisan route:cache -q
chown -R www-data:www-data "${WEB_ROOT}"
chmod -R ug+rwX "${WEB_ROOT}/storage" "${WEB_ROOT}/bootstrap/cache"

echo "==> [6/8] Nginx vhost (+ micro-cache khusus situs ini)"
cat > "/etc/nginx/sites-available/${DOMAIN}.conf" <<NGINX
fastcgi_cache_path /var/cache/nginx/${SLUG} levels=1:2 keys_zone=${SLUG}:50m max_size=2g inactive=30m;

server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN} www.${DOMAIN};

    root ${WEB_ROOT}/public;
    index index.php;
    charset utf-8;
    client_max_body_size 60M;

    gzip on;
    gzip_types text/html text/xml application/xml text/css application/javascript;

    location / { try_files \$uri \$uri/ /index.php?\$query_string; }
    location = /favicon.ico { access_log off; log_not_found off; }

    location ~ \.php\$ {
        fastcgi_pass unix:${PHP_SOCK};
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;

        fastcgi_cache ${SLUG};
        fastcgi_cache_key "\$scheme\$request_method\$host\$request_uri";
        fastcgi_cache_valid 200 301 302 10m;
        fastcgi_cache_valid 404 410 1m;
        fastcgi_cache_lock on;
        fastcgi_no_cache \$cookie_laravel_session \$http_authorization;
        fastcgi_cache_bypass \$cookie_laravel_session \$http_authorization;
        add_header X-Cache \$upstream_cache_status;
        fastcgi_read_timeout 1800;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
NGINX
mkdir -p "/var/cache/nginx/${SLUG}" && chown www-data: "/var/cache/nginx/${SLUG}"
ln -sf "/etc/nginx/sites-available/${DOMAIN}.conf" "/etc/nginx/sites-enabled/${DOMAIN}.conf"
nginx -t && systemctl reload nginx

echo "==> [7/8] Worker queue khusus situs ini (supervisor)"
cat > "/etc/supervisor/conf.d/daya-${SLUG}.conf" <<SUP
[program:daya-${SLUG}]
command=php ${WEB_ROOT}/artisan queue:work --sleep=3 --tries=1 --max-time=3600 --timeout=1800
directory=${WEB_ROOT}
user=www-data
numprocs=2
process_name=%(program_name)s_%(process_num)02d
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
stopwaitsecs=1810
stdout_logfile=${WEB_ROOT}/storage/logs/worker.log
stderr_logfile=${WEB_ROOT}/storage/logs/worker.err.log
SUP
supervisorctl reread >/dev/null && supervisorctl update >/dev/null

echo "==> [8/8] SSL (Let's Encrypt)"
certbot --nginx -d "${DOMAIN}" -d "www.${DOMAIN}" --non-interactive --agree-tos \
        -m "admin@${DOMAIN}" --redirect || echo "   (Lewati/ulangi manual: certbot --nginx -d ${DOMAIN})"

# Cron penjadwalan (sekali per VPS, aman bila diulang)
CRON_LINE="* * * * * cd ${WEB_ROOT} && php artisan schedule:run >> /dev/null 2>&1"
( crontab -l 2>/dev/null | grep -Fv "${WEB_ROOT}" ; echo "${CRON_LINE}" ) | crontab -

echo ""
echo "================================================================="
echo "SELESAI — ${DOMAIN} adalah instalasi MANDIRI DAYA AI."
echo "  Direktori    : ${WEB_ROOT}"
echo "  Database     : ${DB_NAME} (user: ${DB_USER})"
echo "  Admin        : https://${DOMAIN}/admin"
echo "  Login        : ${ADMIN_EMAIL} / ${ADMIN_PASS}   <- CATAT di password manager"
echo "  IndexNow key : ${INDEXNOW_KEY}"
echo "  AI           : ${AI_DRIVER} / ${AI_MODEL} $([ -n "${AI_API_KEY}" ] && echo '(kunci terpasang)' || echo '(KUNCI BELUM DIISI)')"
echo ""
echo "Langkah berikutnya:"
echo "  1. Login admin -> Pengaturan: nama brand, tagline, NOMOR WHATSAPP (wajib)."
echo "  2. Menu 'Mulai Cepat': isi kata kunci + wilayah -> semua terisi otomatis."
echo "  3. Periksa 5-10 halaman sampel, lalu publish bertahap."
echo "  4. Submit https://${DOMAIN}/sitemap.xml ke Google Search Console."
echo "================================================================="
