# Panduan Produksi — Deploy & Skala Jutaan Halaman

Bagian operasional: menjalankan mesin dengan andal & cepat di server sungguhan.
Semua berkas ada di folder `deploy/`.

## Kebutuhan server
- PHP 8.4 (+ ekstensi: pdo_mysql, mbstring, redis/phpredis, openssl, curl, gd)
- MySQL 8 / MariaDB 10.6+
- Redis (cache + queue)
- Nginx + PHP-FPM
- Supervisor (worker antrian)

## Langkah deploy (ringkas)
```bash
# 1) Kode & dependensi
git clone <repo> /var/www/cegu && cd /var/www/cegu
composer install --no-dev --optimize-autoloader

# 2) Konfigurasi
cp deploy/.env.production.example .env    # lalu isi nilainya
php artisan key:generate
php artisan migrate --force
php artisan storage:link

# 3) Cache framework (WAJIB di produksi)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4) Data awal (bila perlu)
php artisan db:seed --force               # konten/template default (bila dipakai)
```

## Worker antrian (Supervisor)
```bash
sudo cp deploy/supervisor/cegu-worker.conf /etc/supervisor/conf.d/
# sesuaikan path & user di dalamnya
sudo supervisorctl reread && sudo supervisorctl update
sudo supervisorctl start cegu-worker:*
```
Worker inilah yang menjalankan Generate, Publish, IndexNow, dan pengisian AI.

## Nginx + micro-cache
```bash
sudo cp deploy/nginx/cegu.conf /etc/nginx/sites-available/cegu
sudo ln -s /etc/nginx/sites-available/cegu /etc/nginx/sites-enabled/
sudo mkdir -p /var/cache/nginx/cegu && sudo chown www-data: /var/cache/nginx/cegu
sudo nginx -t && sudo systemctl reload nginx
```
Micro-cache membuat halaman pSEO dilayani dari cache Nginx (bukan PHP) selama
5 menit → tahan gempuran crawler. Cek header `X-Cache: HIT/MISS`.

## Redis
```bash
sudo cp deploy/redis/redis-cegu.conf /etc/redis/redis.conf.d/   # atau merge manual
sudo systemctl restart redis
```
`maxmemory-policy allkeys-lru` mencegah Redis kehabisan RAM.

## Cron (penjadwalan)
Tambahkan satu baris:
```
* * * * * cd /var/www/cegu && php artisan schedule:run >> /dev/null 2>&1
```
Otomatis: IndexNow harian, warming cache tiap jam, refresh GSC tiap 6 jam
(masing-masing hanya jalan bila fiturnya dikonfigurasi).

## Urutan "go-live" yang sehat (anti-penalti)
1. Isi stok konten sampai hijau (AI atau manual) + minimal 2 fakta lokal/CSV.
2. Generate + publish **1 kota/provinsi dulu**, jangan semua.
3. Verifikasi Search Console, submit `sitemap.xml`, aktifkan IndexNow.
4. Pantau indexing & `X-Cache`; ukur klik WhatsApp di dashboard.
5. Setelah stabil & terindeks sehat, **skalakan bertahap** wilayah berikutnya.

## Saat publish besar / ganti template
```bash
php artisan pages:warm --limit=5000                 # panaskan cache app
sudo rm -rf /var/cache/nginx/cegu/* && sudo systemctl reload nginx   # purge Nginx
```

## Checklist keamanan
- `APP_DEBUG=false`, `APP_ENV=production`.
- `.env` tidak masuk Git; kunci API hanya di `.env`.
- `CEGU_TEMPLATE_BLADE=false` kecuali operator template 100% tepercaya.
- Batasi akses `/admin` (mis. IP allowlist di Nginx bila memungkinkan).
