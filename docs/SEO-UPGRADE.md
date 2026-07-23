# Upgrade "Mesin Terbaik" — SEO Maksimal + Indexing Kilat + Keamanan

Peningkatan ini melengkapi mesin agar maksimal di mata Google/mesin pencari,
cepat terindeks, dan lebih aman. Semua **preference-free** (objektif lebih baik)
dan **backward-compatible**.

## 1. Meta sosial & kontrol index (lebih lengkap)

`SeoService` kini menghasilkan juga: `image` (Open Graph), `image_alt`,
`site_name`, `locale`, `type`, dan `robots`. `layouts/site.blade.php` memancarkan:

- `og:image`, `og:image:alt`, `og:site_name`, `og:locale`, `og:type`
- **Twitter Card** penuh (`summary_large_image` bila ada gambar)
- `theme-color`
- `robots` yang **bisa dikontrol** (bukan lagi hardcoded)

Sumber gambar OG (urut prioritas): Setting `og_image` → `hero_image` →
`organization_logo` → `logo_image`. Isi salah satu di panel Pengaturan.

**Soft-launch / staging tanpa terindeks:** set Setting `default_robots` =
`noindex,follow`. Balikkan ke `index,follow` saat siap.

Setting opsional baru (semua ada fallback): `og_image`, `og_locale`
(default `id_ID`), `theme_color` (default `#1a9e55`), `default_robots`.

## 2. IndexNow — indexing kilat

Begitu halaman **dipublish**, URL-nya otomatis dikirim ke IndexNow
(Bing, Yandex, Naver, Seznam) → jauh lebih cepat terindeks daripada menunggu
crawl. Aman: **no-op** bila `INDEXNOW_KEY` kosong, dan kegagalan tidak pernah
menghentikan proses publish.

Aktivasi:
1. Buat key hex acak (mis. `openssl rand -hex 16`).
2. `.env` → `INDEXNOW_KEY=xxxxxxxx...` lalu `php artisan config:clear`.
3. File verifikasi otomatis tersaji di `https://domain-anda/indexnow.txt`.

Kirim manual (mis. saat pertama pasang untuk halaman lama):
```bash
php artisan indexnow:submit               # 10.000 URL terbaru
php artisan indexnow:submit --limit=50000
php artisan indexnow:submit --all         # semua (perhatikan kuota harian)
```

## 3. robots.txt → sitemap (dinamis)

`robots.txt` kini route dinamis yang otomatis menunjuk ke `/sitemap.xml`
(URL absolut, ikut domain) dan memblok `/admin` & `/login`.

> **WAJIB:** hapus file statis `public/robots.txt` (sudah dihapus di paket ini)
> agar route dinamis yang dipakai. Bila deploy ulang menaruh file statis lagi,
> hapus lagi.

## 4. Keamanan: pengaman Blade di template

Dukungan Blade (eksekusi PHP) di template kini butuh **DUA** saklar aktif:
`CEGU_TEMPLATE_BLADE=true` di `.env` **dan** toggle `template_blade_enabled` di
Pengaturan. Artinya, seandainya database dikompromikan, penyerang tetap tidak
bisa menyalakan eksekusi PHP lewat template. Default: **mati** (aman).

---

## File yang ditambahkan
```
app/Services/IndexNowService.php
app/Console/Commands/IndexNowSubmitCommand.php
```

## File yang diubah
```
app/Services/SeoService.php                 # field og/robots/locale
resources/views/layouts/site.blade.php      # meta sosial + twitter + theme-color
app/Jobs/PublishPagesJob.php                # ping IndexNow saat publish
app/Services/PageRenderer.php               # pengaman Blade (dua saklar)
routes/web.php                              # /robots.txt & /indexnow.txt dinamis
config/services.php                         # blok 'indexnow'
config/cegu.php                             # 'template_blade'
.env.example                                # INDEXNOW_KEY, CEGU_TEMPLATE_BLADE
public/robots.txt                           # DIHAPUS (diganti route)
```

## Setelah pasang
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
# pastikan worker antrian jalan untuk publish + IndexNow:
php artisan queue:work
```

---

## 5. Integrasi Google Search Console & Analytics (panel)

Sebelumnya belum ada tempat menyimpan kode Google — kini tersedia di
**Admin → Pengaturan → kartu "🔗 Integrasi Google"**. Empat kolom:

- **Google Search Console** (metode *Tag HTML*): tempel kodenya, atau seluruh
  tag `<meta name="google-site-verification" content="...">` (sistem mengambil
  kodenya otomatis). Lalu klik *Verify* di Search Console.
  Alternatif metode *File HTML*: unggah `googleXXXX.html` ke folder `public/`.
- **Bing Webmaster** (`msvalidate.01`) — opsional, melengkapi IndexNow.
- **Google Analytics 4** (`G-XXXXXXXXXX`) — otomatis memasang gtag.js.
- **Google Tag Manager** (`GTM-XXXXXXX`) — alternatif GA4; snippet head + noscript
  dipasang otomatis.

Semua dipancarkan di `<head>` seluruh halaman. Kosongkan untuk menonaktifkan.
Setelah menyimpan, cache render otomatis di-refresh — tapi bila halaman
di-cache CDN, purge dulu sebelum menekan *Verify*.

Key Setting baru: `google_site_verification`, `bing_site_verification`,
`google_analytics_id`, `gtm_id`.

## File yang diubah (tambahan bagian 5)
```
app/Http/Controllers/Admin/SettingController.php   # simpan + bersihkan kode
resources/views/admin/settings/edit.blade.php      # kartu Integrasi Google
resources/views/layouts/site.blade.php             # emit verifikasi + GA4/GTM
```
