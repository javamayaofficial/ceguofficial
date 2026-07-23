# Dokumentasi Instalasi — CEGU pSEO Engine

Panduan instalasi untuk **development lokal** dan **produksi VPS (Onidel)**.

## Kebutuhan Sistem

| Komponen | Versi |
|---|---|
| PHP | 8.4 (kompatibel 8.2+) dengan ekstensi: `pdo_mysql`, `mbstring`, `tokenizer`, `xml`, `ctype`, `bcmath`, `curl`, `openssl`, `fileinfo` |
| Composer | 2.x |
| MariaDB / MySQL | MariaDB 10.6+ / MySQL 8+ |
| Nginx | 1.18+ |
| Supervisor | untuk menjaga queue worker |

---

## A. Instalasi Lokal (cepat)

```bash
# 1. Masuk folder proyek & install dependency
composer install

# 2. Siapkan environment
cp .env.example .env
php artisan key:generate

# 3. Atur koneksi database di .env (MySQL) ATAU pakai SQLite untuk coba cepat:
#    - MySQL:  DB_CONNECTION=mysql + DB_DATABASE/DB_USERNAME/DB_PASSWORD
#    - SQLite: DB_CONNECTION=sqlite ; lalu: touch database/database.sqlite

# 4. Migrasi + data contoh (admin, variasi konten, template default, 10 halaman demo)
php artisan migrate --seed

# 5. Jalankan server & worker (dua terminal)
php artisan serve
php artisan queue:work
```

Buka `http://localhost:8000` (publik) dan `http://localhost:8000/admin` (panel).
Login default: **admin@cegu.test / password** (ubah via `.env` `ADMIN_EMAIL`/`ADMIN_PASSWORD` sebelum seed).

---

## B. Instalasi Produksi (VPS)

```bash
# 1. Clone & dependency (tanpa dev)
git clone <repo> /var/www/cegu && cd /var/www/cegu
composer install --no-dev --optimize-autoloader

# 2. Environment
cp .env.example .env
php artisan key:generate
nano .env        # set APP_URL, DB_*, ADMIN_*, APP_ENV=production, APP_DEBUG=false

# 3. Database
mysql -u root -p -e "CREATE DATABASE cegu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate --force
php artisan db:seed --force          # opsional: isi variasi konten + template default

# 4. Optimasi & permission
php artisan config:cache
php artisan route:cache
php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache

# 5. Web server
cp deploy/nginx.conf.example /etc/nginx/sites-available/cegu
# edit server_name, root, versi PHP-FPM, lalu:
ln -s /etc/nginx/sites-available/cegu /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx

# 6. Queue worker (Supervisor)
cp deploy/supervisor-cegu-worker.conf.example /etc/supervisor/conf.d/cegu-worker.conf
supervisorctl reread && supervisorctl update && supervisorctl status

# 7. HTTPS
certbot --nginx -d your-domain.com
```

### Setelah deploy ulang / update kode
```bash
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart        # wajib agar worker memuat kode terbaru
```

---

## Tuning untuk skala besar (target jutaan halaman)

- **MySQL**: `innodb_buffer_pool_size ≈ 8G` (pada VPS 16 GB RAM).
- **Worker**: naikkan `numprocs` Supervisor ke 8 saat batch generate besar.
- **Cache halaman**: aktifkan full-page cache (Nginx FastCGI cache atau paket Laravel)
  untuk halaman published agar render tidak mengenai PHP tiap request.
- **Swap**: sediakan 4–8 GB sebagai pengaman saat generate.

## Verifikasi cepat
```bash
php artisan cegu:stats        # ringkasan jumlah halaman & sitemap
curl -I https://your-domain.com/sitemap.xml
```

## Google Search Console
1. Verifikasi domain (DNS / file HTML / tag meta).
2. Submit `https://your-domain.com/sitemap.xml` (otomatis jadi sitemap index bila > 50.000 URL).
