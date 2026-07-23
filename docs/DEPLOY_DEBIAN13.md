# Deployment CEGU pSEO Engine — Debian 13 (Trixie) + PHP 8.5 + Cloudflare Tunnel

Panduan **dari VPS kosong** sampai situs online lewat **Cloudflare Tunnel**
(tanpa membuka port 80/443 ke publik), dengan **PHP 8.5.7** dan queue worker.

Debian 13 membawa PHP 8.4 di repo resmi; untuk **PHP 8.5** kita pakai repo
**Ondřej Surý** (`packages.sury.org`). TLS ditangani di edge Cloudflare, jadi
**tidak perlu Certbot**.

> Target: domain `your-domain.com`, app di `/var/www/cegu`, dijalankan sebagai
> user `www-data`. Ganti semua nilai yang ditandai.

Ringkasan alur: **Sistem → Paket (PHP 8.5) → Database → Aplikasi → Nginx (lokal) → Worker → Cloudflare Tunnel → Verifikasi**.

---

## 0. Prasyarat

- VPS Debian 13 (Trixie) — minimal **2 vCPU / 4 GB RAM** untuk MVP; **8 GB+**
  bila menargetkan jutaan halaman (lihat [Tuning](#12-tuning-skala-besar)).
- Akses root / sudo.
- Domain sudah terdaftar & **nameserver-nya diarahkan ke Cloudflare** (akun
  Cloudflare gratis cukup). DNS A record tidak perlu disetel manual — tunnel
  yang membuatnya.

Login sebagai root, lalu update sistem:

```bash
apt update && apt -y full-upgrade
timedatectl set-timezone Asia/Jakarta      # opsional, samakan log
```

---

## 1. Firewall (UFW)

Dengan Cloudflare Tunnel, koneksi bersifat **outbound** (cloudflared menelepon
keluar ke jaringan Cloudflare). Jadi **port 80/443 tidak perlu dibuka** — hanya
SSH.

```bash
apt -y install ufw
ufw allow OpenSSH
ufw --force enable
ufw status
```

> Manfaat keamanan: origin tidak punya port HTTP/HTTPS yang terekspos ke
> internet sama sekali. Nginx hanya mendengarkan di localhost.

---

## 2. Install paket + PHP 8.5 (repo Surý)

```bash
apt -y install nginx mariadb-server supervisor git unzip curl ca-certificates lsb-release apt-transport-https

# Tambahkan repo Surý (PHP 8.5 belum ada di repo resmi Debian 13)
curl -sSLo /usr/share/keyrings/sury-php.gpg https://packages.sury.org/php/apt.gpg
echo "deb [signed-by=/usr/share/keyrings/sury-php.gpg] https://packages.sury.org/php/ trixie main" \
  > /etc/apt/sources.list.d/sury-php.list
apt update

# PHP 8.5 + FPM + ekstensi yang dibutuhkan CEGU
apt -y install php8.5-fpm php8.5-cli php8.5-mysql php8.5-mbstring \
  php8.5-xml php8.5-bcmath php8.5-curl php8.5-zip php8.5-intl php8.5-gd php8.5-opcache

# Composer (global)
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# Node.js 22 LTS (build aset Vite/Tailwind) — dari NodeSource
curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
apt -y install nodejs
```

Cek versi (harus **8.5.7** atau patch terbaru di seri 8.5):

```bash
php -v
composer -V
node -v && npm -v
```

> Ekstensi `pdo_mysql, mbstring, tokenizer, xml, ctype, bcmath, curl, openssl,
> fileinfo` semua tercakup (`tokenizer/ctype/openssl/fileinfo` bawaan php8.5-cli).
>
> **Catatan composer.json**: `config.platform.php` saat ini `8.4.0`. Itu hanya
> memengaruhi resolusi dependency (aman dijalankan di runtime 8.5). Bila ingin
> selaras, ubah ke `8.5.7` lalu `composer update --lock`.

---

## 3. Database MariaDB

```bash
mysql_secure_installation
```

Buat database + user khusus aplikasi:

```bash
mysql -u root -p <<'SQL'
CREATE DATABASE cegu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'cegu'@'localhost' IDENTIFIED BY 'GANTI_PASSWORD_KUAT';
GRANT ALL PRIVILEGES ON cegu.* TO 'cegu'@'localhost';
FLUSH PRIVILEGES;
SQL
```

---

## 4. Ambil kode & dependency

```bash
git clone <URL_REPO> /var/www/cegu
cd /var/www/cegu

composer install --no-dev --optimize-autoloader

npm ci
npm run build
rm -rf node_modules        # opsional: aset hasil build sudah ada di public/build
```

---

## 5. Konfigurasi environment

```bash
cp .env.example .env
php artisan key:generate
nano .env
```

Set nilai berikut di `.env`:

```ini
APP_NAME=CEGU
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com        # https — TLS diterminasi di Cloudflare
APP_LOCALE=id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cegu
DB_USERNAME=cegu
DB_PASSWORD=GANTI_PASSWORD_KUAT

ADMIN_EMAIL=admin@your-domain.com
ADMIN_PASSWORD=GANTI_PASSWORD_ADMIN

QUEUE_CONNECTION=database
CACHE_STORE=file
SESSION_DRIVER=database
```

### Trusted proxy (WAJIB di belakang Cloudflare Tunnel)

Request masuk ke Nginx dari cloudflared (localhost). Agar Laravel tahu skema
asli **https** (penting untuk canonical & sitemap yang jadi inti SEO), percayai
proxy. Edit `bootstrap/app.php` — bagian `withMiddleware`:

```php
use Illuminate\Http\Request;

->withMiddleware(function (Middleware $middleware): void {
    $middleware->trustProxies(at: '*', headers:
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO
    );
})
```

Karena trafik hanya tiba via tunnel di loopback, `at: '*'` aman di sini. (Jika
masih ada URL `http://` yang bocor, tambahkan `URL::forceScheme('https')` di
`AppServiceProvider::boot()` saat `APP_ENV=production`.)

---

## 6. Migrasi, seed & cache

```bash
php artisan migrate --force
php artisan db:seed --force          # admin + variasi konten + template default

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link             # bila ada aset di storage/public
```

### Permission

```bash
chown -R www-data:www-data /var/www/cegu
find /var/www/cegu -type d -exec chmod 755 {} \;
find /var/www/cegu -type f -exec chmod 644 {} \;
chmod -R ug+rwx /var/www/cegu/storage /var/www/cegu/bootstrap/cache
```

---

## 7. Nginx (hanya localhost)

Karena tunnel yang menghadap publik, Nginx cukup mendengarkan di loopback.

```bash
cp deploy/nginx.conf.example /etc/nginx/sites-available/cegu
nano /etc/nginx/sites-available/cegu
```

Sesuaikan di file tsb:
- Ganti baris listen menjadi loopback saja:
  ```nginx
  listen 127.0.0.1:8080;
  # hapus/biarkan: listen [::]:80;  →  tidak perlu untuk tunnel
  ```
- `server_name your-domain.com;`
- `root /var/www/cegu/public;`
- Socket FPM: `fastcgi_pass unix:/var/run/php/php8.5-fpm.sock;` **(ubah dari 8.4 → 8.5)**

Aktifkan & matikan default:

```bash
ln -s /etc/nginx/sites-available/cegu /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx
systemctl enable --now php8.5-fpm
```

> Kita pakai port **8080** di localhost agar config tunnel di bawah merujuk ke
> `http://localhost:8080`. Boleh tetap `80` asal konsisten dengan ingress tunnel.

---

## 8. Queue worker (Supervisor)

```bash
cp deploy/supervisor-cegu-worker.conf.example /etc/supervisor/conf.d/cegu-worker.conf
nano /etc/supervisor/conf.d/cegu-worker.conf      # pastikan path /var/www/cegu & php (8.5)

supervisorctl reread
supervisorctl update
supervisorctl status
```

> Setiap habis deploy/ubah kode: `php artisan queue:restart`.

---

## 9. Cloudflare Tunnel

### 9.1 Install cloudflared

```bash
curl -fsSL https://pkg.cloudflare.com/cloudflare-main.gpg \
  | tee /usr/share/keyrings/cloudflare-main.gpg >/dev/null
echo "deb [signed-by=/usr/share/keyrings/cloudflare-main.gpg] https://pkg.cloudflare.com/cloudflared any main" \
  > /etc/apt/sources.list.d/cloudflared.list
apt update && apt -y install cloudflared
```

### 9.2 Login & buat tunnel

```bash
cloudflared tunnel login          # buka URL yang muncul, pilih domain di dashboard
cloudflared tunnel create cegu    # menghasilkan UUID + file kredensial JSON
```

Catat **UUID tunnel** dan path kredensial (mis. `/root/.cloudflared/<UUID>.json`).

### 9.3 Config ingress

Buat `/etc/cloudflared/config.yml`:

```yaml
tunnel: <UUID-TUNNEL>
credentials-file: /root/.cloudflared/<UUID-TUNNEL>.json

ingress:
  - hostname: your-domain.com
    service: http://localhost:8080
  - hostname: www.your-domain.com
    service: http://localhost:8080
  - service: http_status:404
```

### 9.4 Arahkan DNS & jalankan sebagai service

```bash
cloudflared tunnel route dns cegu your-domain.com
cloudflared tunnel route dns cegu www.your-domain.com

cloudflared service install      # daftarkan systemd service
systemctl enable --now cloudflared
systemctl status cloudflared     # harus active (running) + "Registered tunnel connection"
```

> **TLS**: aktifkan SSL/TLS mode **Full** di dashboard Cloudflare. Origin tetap
> HTTP localhost (aman, karena jalur cloudflared↔Cloudflare sudah terenkripsi).
> Tidak perlu Certbot.
>
> **Alternatif (remotely-managed)**: buat tunnel dari dashboard
> *Zero Trust → Networks → Tunnels*, lalu jalankan dengan token:
> `cloudflared service install <TOKEN>`. Lewati langkah 9.2–9.4 CLI.

---

## 10. Verifikasi

```bash
php artisan cegu:stats                            # ringkasan halaman & sitemap
curl -I http://localhost:8080                     # 200 OK (origin lokal)
curl -I https://your-domain.com                   # 200 OK lewat Cloudflare
curl -I https://your-domain.com/sitemap.xml       # 200, content-type xml
```

Buka di browser:
- `https://your-domain.com/admin` → login `ADMIN_EMAIL` / `ADMIN_PASSWORD`.
- Contoh halaman generate: `/les-privat-matematika/bandung/cicendo/pajajaran`.

Pastikan canonical & URL di sitemap berskema **https** (efek trusted proxy di
[bagian 5](#trusted-proxy-wajib-di-belakang-cloudflare-tunnel)).

Alur kerja: **Import CSV → Generate → Draft → Publish → Sitemap** (via /admin
atau `php artisan cegu:import <file.csv>` & `php artisan cegu:publish`).

---

## 11. Update / deploy ulang

```bash
cd /var/www/cegu
php artisan down
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build                           # bila aset berubah
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart
chown -R www-data:www-data /var/www/cegu/storage /var/www/cegu/bootstrap/cache
php artisan up
```

cloudflared tidak perlu di-restart kecuali `config.yml` berubah
(`systemctl restart cloudflared`).

---

## 12. Tuning skala besar

(target jutaan halaman — selaras `docs/INSTALL.md`)

- **MariaDB**: di `/etc/mysql/mariadb.conf.d/50-server.cnf` set
  `innodb_buffer_pool_size ≈ 50–60%` RAM (mis. `8G` pada VPS 16 GB), lalu
  `systemctl restart mariadb`.
- **Worker**: naikkan `numprocs` Supervisor ke `8` saat batch generate besar.
- **Full-page cache**: aktifkan Nginx FastCGI cache untuk halaman published agar
  render tidak mengenai PHP tiap request. Tambah juga **Cache Rules** di
  Cloudflare untuk meng-cache HTML halaman published di edge.
- **PHP-FPM**: naikkan `pm.max_children` di `/etc/php/8.5/fpm/pool.d/www.conf`
  sesuai RAM. **OPcache** sudah aktif — pastikan `opcache.enable=1` di
  `/etc/php/8.5/fpm/conf.d/`.
- **Swap**: sediakan 4–8 GB sebagai pengaman saat generate massal:
  ```bash
  fallocate -l 4G /swapfile && chmod 600 /swapfile
  mkswap /swapfile && swapon /swapfile
  echo '/swapfile none swap sw 0 0' >> /etc/fstab
  ```

---

## Catatan versi & perbedaan

| Hal | Nilai |
|---|---|
| OS | Debian 13 (Trixie) |
| PHP | **8.5.7** via repo Surý (`packages.sury.org`, codename `trixie`) |
| Socket FPM | `php8.5-fpm.sock` |
| TLS | Cloudflare edge (mode Full) — **tanpa Certbot** |
| Port publik origin | **tidak ada** (hanya SSH); HTTP lewat Cloudflare Tunnel |
| Real visitor IP | header `CF-Connecting-IP` / `X-Forwarded-For` (sudah dipercaya via trustProxies) |

PHP 8.4 ada native di Debian 13, tetapi **8.5 belum** — karena itu panduan ini
menambah repo Surý. Bila cukup dengan 8.4, lewati langkah repo Surý dan ganti
semua `php8.5` → `php8.4`.
