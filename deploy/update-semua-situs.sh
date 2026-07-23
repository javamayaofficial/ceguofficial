#!/usr/bin/env bash
# =============================================================================
# update-semua-situs.sh — Sebarkan update mesin ke SEMUA instalasi terpisah
#
# Karena tiap domain adalah instalasi mandiri dari source code yang sama,
# perbaikan/fitur baru mesin harus disalin ke tiap situs. Skrip ini
# melakukannya otomatis untuk semua situs di /var/www:
#
#   1. rsync KODE dari source master → situs (TANPA menyentuh .env, storage,
#      database, dan uploads milik situs — data tiap situs aman)
#   2. composer install
#   3. php artisan migrate --force   (migrasi baru bila ada)
#   4. bersihkan & bangun ulang cache config/route
#   5. queue:restart                 (worker memuat kode baru)
#
# Pemakaian:
#   sudo bash update-semua-situs.sh            # update semua situs
#   sudo bash update-semua-situs.sh hebalku.com  # update satu situs saja
#
# Uji dulu di SATU situs (paling kecil risikonya) sebelum menjalankan ke semua.
# =============================================================================
set -euo pipefail

SOURCE_DIR="${SOURCE_DIR:-/opt/daya-engine}"
SITES_DIR="/var/www"
ONLY="${1:-}"

[ -d "${SOURCE_DIR}" ] || { echo "Source master tidak ditemukan di ${SOURCE_DIR}"; exit 1; }

update_site() {
    local site_dir="$1"
    local domain; domain="$(basename "${site_dir}")"

    # Hanya proses direktori yang merupakan instalasi mesin ini
    [ -f "${site_dir}/artisan" ] || return 0

    # Sinkron otak AI: tambahkan file pengetahuan BARU saja (-n = tidak menimpa
    # yang sudah disesuaikan operator situs).
    if [ -d "${site_dir}/database/data/brain" ]; then
        mkdir -p "${site_dir}/storage/app/brain"
        cp -n "${site_dir}/database/data/brain/"*.md "${site_dir}/storage/app/brain/" 2>/dev/null || true
    fi
    grep -q "CEGU_PAGE_CACHE_TTL" "${site_dir}/.env.example" 2>/dev/null || return 0

    echo "==> Update: ${domain}"

    rsync -a --delete \
        --exclude '.env' \
        --exclude 'storage/' \
        --exclude 'bootstrap/cache/' \
        --exclude 'vendor/' \
        --exclude 'node_modules/' \
        --exclude 'public/uploads/' \
        --exclude 'database/database.sqlite' \
        "${SOURCE_DIR}/" "${site_dir}/"

    (
        cd "${site_dir}"
        composer install --no-dev --optimize-autoloader --no-interaction --quiet
        php artisan migrate --force -q
        php artisan config:clear -q && php artisan config:cache -q
        php artisan route:clear -q  && php artisan route:cache -q
        php artisan view:clear -q
        php artisan queue:restart -q
    )
    chown -R www-data:www-data "${site_dir}"
    echo "    OK — ${domain} terbarui."
}

if [ -n "${ONLY}" ]; then
    update_site "${SITES_DIR}/${ONLY}"
else
    shopt -s nullglob
    for d in "${SITES_DIR}"/*/; do
        update_site "${d%/}"
    done
fi

echo ""
echo "Selesai. Cek cepat tiap situs: buka /admin dan satu salespage acak."
