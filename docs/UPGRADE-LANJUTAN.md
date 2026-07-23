# Upgrade Lanjutan — 6 Peningkatan "Mesin Terbaik"

Menutup celah yang tersisa: keunikan, pelacakan lead, schema lokal, skala,
monitoring, dan penanganan halaman dicabut. Semua backward-compatible.

## 1. Anti halaman tipis (keunikan) + canonical cerdas
- `UniquenessService` menghitung "fakta lokal riil" per halaman dari kolom CSV
  `extra` (harga, jumlah_tutor, landmark, …). Koordinat lat/lng TIDAK dihitung
  sebagai konten.
- Halaman yang **tipis** (fakta lokal < `CEGU_THIN_MIN_FACTS`, default 2) otomatis
  meng-**canonical ke hub kecamatan** — memusatkan sinyal & mencegah ribuan
  halaman kelurahan kembar dinilai duplikat/spam.
- Nonaktifkan dengan `CEGU_THIN_CANONICAL_HUB=false`.
- **Rekomendasi:** isi minimal 2 kolom `extra` bernilai riil per baris CSV.

## 2. Pelacakan lead (klik WhatsApp)
- Setiap klik tombol WA memicu: **event GA4** `whatsapp_click` (service/city/source)
  **dan** beacon ke server (`/track/wa`) → tabel `lead_clicks`.
- Dashboard menampilkan **total klik 30 hari, hari ini, dan halaman teratas** —
  dasar untuk menggandakan pola yang menang.
- Butuh migrasi: `php artisan migrate`.
- Endpoint stateless, di-throttle, dikecualikan CSRF (aman untuk halaman ter-cache).

## 3. Schema LocalBusiness + geo
- JSON-LD kini menambah node **LocalBusiness** (alamat, telepon, areaServed).
- Bila baris CSV punya kolom `lat` & `lng`, otomatis menambah `GeoCoordinates`
  (sinyal SEO lokal kuat). Tanpa geo pun tetap emit alamat/areaServed.
- **Tips:** pakai dataset wilayah Indonesia gratis untuk mengisi lat/lng per
  kelurahan — jangan pakai Google Geocoding berbayar.

## 4. Skala & proteksi
- **Rate limit** halaman publik per IP (`CEGU_PUBLIC_RATE_LIMIT`, default 120/mnt)
  meredam bot agresif.
- **Cache warming:** `php artisan pages:warm --limit=2000` memanaskan HTML top
  halaman setelah publish massal.
- **Produksi disarankan Redis + LRU** (di `.env`):
  ```env
  CACHE_STORE=redis
  QUEUE_CONNECTION=redis
  ```
  dan di `redis.conf`: `maxmemory 2gb` + `maxmemory-policy allkeys-lru`.

## 5. Monitoring Google Search Console
- `SearchConsoleService` menarik klik/impresi/CTR/posisi via Search Analytics API
  memakai **service account** (JWT ditandatangani openssl — tanpa paket tambahan).
- Dashboard menampilkan ringkasan 28 hari (di-cache 6 jam, aman bila belum diset).
- CLI: `php artisan gsc:stats --days=28`.
- Setup: aktifkan **Search Console API** di Google Cloud, buat service account,
  unduh JSON → set `GSC_CREDENTIALS` (path/JSON) & `GSC_SITE_URL`, lalu tambahkan
  email service account sebagai **pengguna** di properti Search Console.

## 6. 410 Gone untuk halaman dicabut
- Halaman yang pernah tayang lalu di-unpublish kini membalas **410 Gone**
  (bukan 404) → Google mencabutnya dari indeks lebih cepat. Draft murni tetap 404.

---

## File baru
```
app/Services/UniquenessService.php
app/Models/LeadClick.php
app/Http/Controllers/LeadTrackController.php
app/Console/Commands/WarmPagesCommand.php
app/Console/Commands/GscStatsCommand.php
app/Services/SearchConsole/GoogleServiceAccountToken.php
app/Services/SearchConsole/SearchConsoleService.php
database/migrations/2026_07_21_000001_create_lead_clicks_table.php
tests/Feature/SeoEngineUpgradesTest.php
```

## File diubah
```
app/Services/SeoService.php                       # canonical cerdas + LocalBusiness/geo
app/Http/Controllers/PageController.php           # 410 Gone
app/Http/Controllers/Admin/DashboardController.php# widget lead + GSC
resources/views/admin/dashboard.blade.php         # kartu lead + GSC
resources/views/layouts/site.blade.php            # JS pelacak klik WA
app/Providers/AppServiceProvider.php              # rate limiter 'pseo'
routes/web.php                                     # /track/wa + throttle catch-all
bootstrap/app.php                                  # CSRF except track/wa
config/services.php                                # blok 'gsc'
config/cegu.php                                    # thin_*, public_rate_limit
.env.example
```

## Setelah pasang
```bash
php artisan migrate
php artisan config:clear && php artisan route:clear && php artisan view:clear
php artisan queue:work            # untuk publish + IndexNow
# opsional:
php artisan pages:warm --limit=2000
php artisan gsc:stats
```
