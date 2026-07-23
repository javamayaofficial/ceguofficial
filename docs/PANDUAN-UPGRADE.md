# Panduan Upgrade & Alur Penggunaan yang Benar
### Patch performa & SEO — 11 Juli 2026

Dokumen ini menjelaskan (A) apa yang diubah oleh patch, (B) cara memasangnya,
(C) konfigurasi produksi, dan (D) **alur kerja bertahap yang benar** agar 2 juta
halaman tidak berakhir di-deindex Google.

---

## A. Apa yang diubah

| # | File | Perubahan | Alasan |
|---|------|-----------|--------|
| 1 | `database/migrations/2026_07_11_000001_add_performance_indexes_to_pages_table.php` | **BARU** — 2 indeks komposit | Query internal link (`village_id+status` dan `service_id+city_id+status`) sebelumnya tidak tercakup indeks yang selektif; di 2 juta baris ini hotspot terbesar. |
| 2 | `app/Http/Controllers/CategoryController.php` | Deduplikasi kota/kecamatan dipindah ke SQL (`DISTINCT` subquery) + cache | Versi lama menarik SEMUA baris pages untuk satu layanan ke memori PHP (bisa ratusan ribu row per pageview) → pasti tumbang di skala besar. |
| 3 | `app/Services/SitemapService.php` | OFFSET → keyset (batas id per chunk di-cache) | `OFFSET 1.950.000` memaksa DB memindai hampir seluruh indeks setiap kali cache chunk dingin. |
| 4 | `app/Http/Controllers/PageController.php` | Cache HTML halaman (versioned) + header `Cache-Control: public, max-age=600` | Render deterministik → aman di-cache; header membuat Nginx/CDN ikut menyimpan. |
| 5 | `app/Support/RenderCache.php` | **BARU** — cache versi global | Invalidasi murah: admin ubah template/konten → versi naik → semua cache lama hangus otomatis, tanpa menghapus jutaan key. |
| 6 | `config/cegu.php` + `.env.example` | **BARU** — `CEGU_PAGE_CACHE_TTL` (default 900), `CEGU_CATEGORY_CACHE_TTL` (600) | TTL bisa diatur; 0 = nonaktif. |
| 7 | `app/Services/SeoService.php` | Tambah schema `Service` + `provider` + `areaServed` (kota/kecamatan/kelurahan) di JSON-LD | Sinyal entitas bisnis lokal — relevan untuk pencarian lokal dan AI Overview. |
| 8 | `app/Services/SummaryGenerator.php` | Pool summary 3×3×3 (27 kombinasi) → 8×8×8 (**512 kombinasi**) | Summary = sumber meta description; 27 varian untuk jutaan halaman = duplikat masif. |
| 9 | Controller admin (Template/Content/Faq/Setting) | Panggil `RenderCache::bump()` setiap ada perubahan | Perubahan admin langsung terlihat di halaman publik meski cache aktif. |

### Tambahan "Naik Level" (gelombang 2, 11 Juli 2026)

| # | File | Perubahan |
|---|------|-----------|
| 10 | `..._add_extra_data_columns.php` | **BARU** — kolom JSON `extra` di `pages` & `import_rows` |
| 11 | `app/Jobs/ImportCsvJob.php` | Kolom CSV opsional apa pun → data lokal per halaman (nama kolom dinormalisasi, token reserved dilindungi) |
| 12 | `app/Services/PageGenerator.php` | Simpan `extra`; **re-import lokasi sama + kolom baru = memperkaya halaman existing** (merge, tanpa duplikat) |
| 13 | `app/Services/PageRenderer.php` | Data lokal jadi token `{{harga}}` dst. (escaped); token `{{fakta_lokal}}` otomatis; token tak terselesaikan dibersihkan dari output publik |
| 14 | `app/Services/SummaryGenerator.php` | Fakta lokal dianyam TEPAT setelah pembuka → meta description unik per halaman |
| 15 | `app/Services/ContentHealthService.php` | **BARU** — skor kesehatan stok konten vs target + deteksi status onboarding |
| 16 | `admin/dashboard.blade.php` | Panel "Mulai dari sini" (checklist auto-detect) + panel "Kesehatan Stok Konten" (bar per section, label SIAP/BELUM) |
| 17 | `template-default.html` + `SalespageStyles.php` | Blok `{{fakta_lokal}}` terpasang di template default + styling |
| 18 | `tests/Feature/PseoEngineTest.php` | +4 test: data lokal, enrich re-import, anti-bocor token, halaman tanpa extra |

### Tambahan "Multi-Niche / Universal" (gelombang 3, 11 Juli 2026)

| # | File | Perubahan |
|---|------|-----------|
| 19 | `app/Services/SummaryGenerator.php` | Kalimat summary DIPINDAH dari kode ke pool DB (section `summary_open/bridge/close/filler`, token `{{usp_text}}` tersedia) + fallback netral bila kosong → summary bisa disetel untuk bisnis apa pun |
| 20 | `app/Models/ContentBlock.php` | 4 section summary baru terdaftar resmi |
| 21 | `app/Services/ContentPackService.php` | **BARU** — pemuat Paket Konten Awal (aditif & idempoten) |
| 22 | `database/seeders/data/packs/*.json` | **BARU** — 4 paket: jasa_umum, herbal, properti, pendidikan (±70–80 variasi + 8 FAQ per paket) |
| 23 | `ContentBlockController` + route + view | Dropdown "Muat Paket" di menu Variasi Konten |
| 24 | `template-default.html` | Seluruh copy pendidikan diganti universal (stats, standar layanan, keunggulan) — diverifikasi bebas istilah tutor/siswa/les/belajar |
| 25 | Views `salespage/home/category` | Brand di nav & footer dari Pengaturan (bukan hardcoded "CEGU"); footer & beranda memakai setting `tagline` baru |
| 26 | `SettingController` + settings view | Kunci `tagline` (slogan bisnis) |
| 27 | `PageRenderer` | Label Fakta Lokal diperluas lintas niche (stok, garansi, pengiriman, luas_tanah, kamar, legalitas, komposisi, izin_bpom, dll.) |
| 28 | `ContentHealthService` | Target kesehatan mencakup pool summary |
| 29 | Tests | +2: pemuatan paket (idempoten) & summary berbasis DB — total 14 test |

Migrasi niche pada instalasi berjalan: seed lama (pendidikan) tetap kompatibel;
untuk ganti niche, kosongkan Variasi Konten & FAQ lama lalu muat paket baru
dan perbarui `brand_name` + `tagline` di Pengaturan.

### Tambahan "Banyak Domain" (gelombang 4, 11 Juli 2026)

| # | File | Perubahan |
|---|------|-----------|
| 30 | `deploy/pasang-situs-baru.sh` | **BARU** — pasang satu instalasi mandiri per domain dalam satu perintah (dir + DB + .env dengan APP_NAME unik + nginx & FastCGI cache + 2 worker supervisor + SSL) |
| 31 | `deploy/update-semua-situs.sh` | **BARU** — sebarkan update mesin ke semua instalasi terpisah (kode saja; .env/DB/storage tiap situs tidak disentuh) + migrate + queue:restart per situs |
| 32 | `docs/PANDUAN-BANYAK-DOMAIN.md` | **BARU** — arsitektur server, kapasitas per VPS, rutinitas portofolio, dan aturan anti-footprint |

### Pengamanan Rilis (gelombang 7, 12 Juli 2026)

| # | File | Perubahan |
|---|------|-----------|
| 48 | `AdminUserSeeder` | Di production, password default (`password`/`ubah-password-ini`) otomatis diganti password acak — dicetak sekali di output seeder |
| 49 | `deploy/pasang-situs-baru.sh` | Kredensial admin acak unik per situs, dicetak di ringkasan akhir |
| 50 | `docs/CHECKLIST-RILIS.md` | **BARU** — checklist pra-rilis satu halaman (syarat PHP 8.4, verifikasi 10 menit, daftar jujur yang belum ada) |

### Tambahan "Data & Import Massal" (gelombang 6, 12 Juli 2026)

| # | File | Perubahan |
|---|------|-----------|
| 41 | `..._add_layanan_list_to_import_batches.php` + `ImportBatch` | **BARU** — kolom daftar layanan untuk mode cross-join |
| 42 | `ImportController` + `ImportCsvJob` + view imports | **Mode cross-join**: CSV lokasi tanpa kolom layanan × daftar layanan di form = tiap lokasi digandakan otomatis untuk semua keyword (data lokal ikut tersalin) |
| 43 | `ContentBlockController@importCsv` + route + view | Import massal variasi konten dari CSV (`section,content,weight`), idempoten, validasi section |
| 44 | `FaqController@importCsv` + route + view | Import massal FAQ dari CSV (`question,answer,layanan`), pencocokan layanan by nama/slug |
| 45 | `app/Support/CsvReader.php` | **BARU** — semua jalur import kini toleran BOM UTF-8 & pemisah titik-koma (jebakan khas Excel Indonesia) |
| 46 | `docs/PANDUAN-DATA-CSV.md` + `docs/contoh-csv/*` | **BARU** — panduan menyusun data (aturan keyword anti-kanibalisasi, strategi gelombang nasional→kota pemenang) + 4 file contoh |
| 47 | Tests | +3: cross-join, import variasi idempoten, toleransi Excel Indonesia — total 19 test |

### Tambahan "Sidik Jari Tema" (gelombang 5, 11 Juli 2026)

| # | File | Perubahan |
|---|------|-----------|
| 33 | `app/Support/ThemeFingerprint.php` | **BARU** — prefix tema per instalasi + varian visual deterministik (6 palet × 4 radius × 3 font × 3 bentuk tombol) + pemilih varian template |
| 34 | `app/Http/Middleware/ApplyThemeFingerprint.php` | **BARU** — mengganti semua class `cegu-*` → `{prefix}-*` di respons HTML publik (admin & sitemap tidak disentuh) |
| 35 | `routes/web.php` | Middleware terpasang di route home + catch-all publik |
| 36 | `app/Support/SalespageStyles.php` | Menyisipkan CSS override deterministik dari sidik jari |
| 37 | `database/seeders/data/template-variant-{b,c}.html` | **BARU** — 2 varian susunan section + label (himpunan token diverifikasi identik dengan default) |
| 38 | `database/seeders/ContentSeeder.php` | Generate `theme_prefix` acak sekali per instalasi; seed 3 varian template; aktifkan satu secara deterministik (pilihan manual admin dihormati) |
| 39 | `SettingController` + settings view | Kunci `theme_prefix` ("Kode Tema") dapat diganti admin untuk mengocok ulang tampilan |
| 40 | Tests | +2: penggantian class menyeluruh tanpa jejak `cegu-`, dan varian visual deterministik-berbeda — total 16 test |

Catatan penting multi-domain: `APP_NAME` WAJIB unik per situs karena menjadi
prefix cache & queue Redis — dua situs dengan APP_NAME sama di satu Redis akan
saling menimpa cache halaman. Skrip pemasang sudah menanganinya otomatis.

Catatan perilaku baru: token yang tidak terselesaikan (mis. `{{harga}}` pada
halaman tanpa data harga) kini otomatis DIHILANGKAN dari output publik — bukan
lagi ditampilkan mentah. Typo token di variasi konten akan tampil kosong, jadi
selalu cek preview setelah mengedit.

Tidak ada dependensi composer baru. Semua 9 test bawaan tetap lolos, ditambah
smoke test end-to-end (salespage 200 + schema Service/areaServed/FAQPage muncul,
kategori 200, sitemap index & chunk 200, cache tersimpan, bump versi bekerja).

---

## B. Cara memasang patch

**Jika ini instalasi baru** — pakai zip ini langsung, ikuti `docs/INSTALL.md` seperti biasa.

**Jika sudah ada instalasi berjalan:**

```bash
# 1. Backup dulu (WAJIB)
mysqldump -u user -p cegu > backup-$(date +%F).sql
cp -r /path/ke/cegu /path/ke/cegu-backup

# 2. Salin file yang berubah (daftar di tabel A) ke instalasi Anda

# 3. Tambahkan 2 baris ini ke .env produksi:
#    CEGU_PAGE_CACHE_TTL=900
#    CEGU_CATEGORY_CACHE_TTL=600

# 4. Jalankan migrasi indeks & bersihkan config
php artisan migrate --force
php artisan config:clear && php artisan cache:clear
```

> Catatan: `migrate` pada tabel `pages` yang sudah berisi jutaan baris akan
> membangun indeks — di MariaDB/MySQL modern ini online DDL, tapi tetap
> jalankan di jam sepi. Perkiraan: beberapa menit untuk 2 juta baris.

---

## C. Konfigurasi produksi (penting di skala besar)

**1. Ganti cache & queue ke Redis.** Default `database` akan menjadikan tabel
`cache`/`jobs` bottleneck baru. Di `.env`:

```
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
```

Lalu batasi memori Redis agar cache halaman tidak membengkak (hanya halaman
yang sedang ramai yang bertahan):

```
# /etc/redis/redis.conf
maxmemory 1gb
maxmemory-policy allkeys-lru
```

**JANGAN** menaikkan `CEGU_PAGE_CACHE_TTL` ke angka besar (mis. 86400) di cache
store `database` — 2 juta HTML × ±40KB = puluhan GB di tabel cache. TTL pendek
(900) + Redis LRU adalah kombinasi yang benar.

**2. (Opsional tapi disarankan) FastCGI cache di Nginx.** Karena halaman kini
mengirim `Cache-Control: public, max-age=600`, Nginx bisa menyerap mayoritas
hit crawler tanpa menyentuh PHP sama sekali. Tambahkan di `nginx.conf`:

```nginx
fastcgi_cache_path /var/cache/nginx/cegu levels=1:2 keys_zone=cegu:100m
                   max_size=2g inactive=30m;

server {
    ...
    location ~ \.php$ {
        ...
        fastcgi_cache cegu;
        fastcgi_cache_key "$scheme$request_method$host$request_uri";
        fastcgi_cache_valid 200 10m;
        # jangan cache admin & login
        fastcgi_no_cache $cookie_laravel_session;
        fastcgi_cache_bypass $cookie_laravel_session;
        add_header X-Cache $upstream_cache_status;
    }
}
```

**3. Worker queue.** Minimal 2 proses supervisor (`queue:work --tries=3
--timeout=650`) — satu cukup untuk generate, tapi dua memberi ruang saat
import dan publish berjalan bersamaan.

---

## D. ALUR PENGGUNAAN YANG BENAR — jangan lompati tahap

Kesalahan paling fatal bukan di kode, tapi di urutan kerja: **mem-publish jutaan
halaman tipis sekaligus**. Google mengklasifikasikan pola itu sebagai *scaled
content abuse / doorway pages* dan bisa men-deindex seluruh domain. Ikuti
tahapan ini secara berurutan.

### Tahap 0 — Persiapan (sebelum menyentuh CSV)

1. Isi **Pengaturan** di admin: brand, nomor WA, pesan WA, organization
   name/url/logo, hero image. (Organization dipakai di schema.)
2. Pastikan domain sudah HTTPS dan `APP_URL` di `.env` benar — canonical dan
   sitemap dibangun dari sini. Salah `APP_URL` = ribuan canonical salah.
3. Verifikasi domain di **Google Search Console** (GSC) sejak hari pertama.

### Tahap 1 — Perbesar pool konten (WAJIB sebelum generate massal)

Seed bawaan hanya: hero 5, intro 4, pain point 4, solusi 4, USP 6, testimoni 5,
CTA 4, FAQ 4. Itu cukup untuk demo, **tidak cukup untuk jutaan halaman**.
Target minimum sebelum generate massal, via menu **Konten** di admin:

| Section | Bawaan | Target minimum |
|---|---|---|
| hero | 5 | 20+ |
| intro | 4 | 20+ |
| pain_point | 4 | 15+ |
| solusi | 4 | 15+ |
| usp | 6 | 15+ |
| testimoni | 5 | 25+ (nama & konteks berbeda-beda) |
| cta | 4 | 10+ |
| FAQ | 4 | 10 global + 3–5 khusus per layanan |

Tips: gunakan token `{{kelurahan}}`, `{{kecamatan}}`, `{{kota}}`, `{{layanan}}`
di dalam variasi agar tiap kombinasi terasa lokal. Buat FAQ per layanan
(fitur sudah ada) — ini pembeda terbesar antar halaman.

### Tahap 2 — Perkaya data CSV (kunci lolos "thin content")

Halaman yang hanya berbeda nama kelurahan = doorway page. Yang membuat halaman
layak diindeks adalah **data unik per lokasi**. Sebisa mungkin siapkan di CSV /
konten Anda hal-hal seperti: kisaran harga per area, jumlah tutor aktif di
kecamatan itu, sekolah/landmark terdekat, jadwal populer. (MVP ini belum punya
kolom CSV ekstra — jadikan ini prioritas pengembangan berikutnya; sementara
itu, kompensasi dengan pool variasi yang besar dan FAQ per layanan.)

### Tahap 3 — Pilot kecil dulu (1 kota)

1. Import CSV **satu kota saja** (mis. 500–2.000 baris) → Generate.
2. QA manual: buka 10–20 halaman acak. Cek: judul benar? summary natural?
   internal link mengarah ke halaman published? tombol WA membawa pesan yang
   benar? Uji schema di https://search.google.com/test/rich-results
   (harus terbaca: BreadcrumbList, FAQPage, Organization, **Service**).
3. Publish pilot → submit `https://domainanda.com/sitemap.xml` di GSC.
4. **Tunggu 2–4 minggu.** Pantau GSC → Indexing → Pages. Metrik kuncinya:
   rasio *Indexed* vs *Crawled – currently not indexed*.

### Tahap 4 — Skala bertahap (bukan sekaligus)

Aturan praktis:

- Rasio terindeks pilot **> 60%** → lanjut, publish per kota, mis. 10.000–
  50.000 halaman per minggu. Fitur **Publish Queue (Start/Pause/Resume)** di
  admin memang dibuat untuk ini — gunakan Pause untuk mengatur ritme.
- Rasio **< 30%** → BERHENTI menambah. Google menganggap kontennya tipis.
  Perbaiki dulu: perbesar pool, tambah FAQ per layanan, tambah data unik.
  Menambah volume di kondisi ini justru mempercepat penalti.
- Jangan pernah menekan "publish semua" untuk 2 juta draft sekaligus.
  Pre-generate 2 juta sebagai **draft** boleh dan aman (memang desainnya
  begitu); yang diatur ketat adalah kecepatan **publish**.

### Tahap 5 — Operasional rutin

- **Pantau GSC mingguan**: Pages (index coverage), Crawl stats, Manual actions.
- **Ubah template/konten kapan saja** — aman: cache otomatis hangus
  (`RenderCache::bump()`), dan karena HTML tidak disimpan, semua halaman
  langsung memakai versi baru.
- Setelah publish gelombang baru, sitemap otomatis ter-update ≤ 5 menit
  (cache TTL). Tidak perlu submit ulang di GSC; cukup sekali di awal.
- Cek beban DB: bila query internal link masih terasa berat, jalankan
  `EXPLAIN` — harus terlihat memakai `pages_village_status_idx` /
  `pages_service_city_status_idx`.

### Ringkasan "jangan salah langkah"

1. ❌ Publish jutaan halaman sekaligus → ✅ bertahap per kota, pantau rasio index.
2. ❌ Generate massal dengan pool bawaan (4–6 varian) → ✅ perbesar pool dulu (Tahap 1).
3. ❌ Cache `database` dengan TTL panjang → ✅ Redis + LRU, TTL 900.
4. ❌ Lupa `APP_URL` → ✅ cek canonical di view-source sebelum publish pertama.
5. ❌ Menilai sukses dari jumlah halaman → ✅ nilai dari halaman TERINDEKS dan lead WA masuk.

---

## E. Verifikasi patch (checklist setelah pasang)

```bash
php artisan test                      # 9 test harus lolos
php artisan migrate:status | tail -3 # migrasi indeks berstatus Ran
curl -sI https://domain/les-.../..../..../....  | grep -i cache-control
# → Cache-Control: max-age=600, public
curl -s https://domain/sitemap.xml | head -5     # sitemapindex muncul
```

Di view-source salespage, blok `<script type="application/ld+json">` kini harus
memuat `"@type":"Service"` dengan `areaServed` berisi kota/kecamatan/kelurahan.
