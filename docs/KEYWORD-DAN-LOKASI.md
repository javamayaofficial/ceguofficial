# Keyword AI + Lokasi Resmi Indonesia (menutup seluruh alur)

Melengkapi mesin jadi: **keyword layanan (AI) × lokasi RESMI (dataset) = jutaan
halaman** — tanpa mengarang nama daerah, plus koordinat resmi untuk schema geo.

## A. Generator keyword longtail (AI)

Menu baru **Admin → Keyword AI**. Isi niche (mis. "les privat") + kata kunci
awal, tekan Generate → daftar keyword longtail Bahasa Indonesia (berbagai intent
& modifier: murah, terdekat, panggilan, 24 jam, segmen SD/SMP/…). Keyword
**tanpa nama kota** (kota ditambахkan otomatis saat cross-join).

Alur pemakaian:
1. **Keyword AI** → Generate → **Salin** daftarnya.
2. **Import CSV** → tempel ke kolom **"Daftar Layanan"** (mode cross-join).
3. Unggah CSV lokasi (lihat bagian B) tanpa kolom `layanan`.
4. Mesin mengalikan setiap lokasi × setiap keyword → jutaan halaman.

CLI (otomasi/batch besar):
```bash
php artisan keywords:generate "les privat" --count=200 --seeds="matematika,fisika" --out=keyword.csv
```

## B. Lokasi & koordinat RESMI Indonesia (terbundel)

Dua dataset ikut dalam paket (`database/data/`, sumber terbuka cahyadsn/wilayah):

- `id-region-coords.csv` — 38 provinsi + 513 kabupaten/kota dengan **lat/lng
  resmi**. Dipakai otomatis oleh schema **LocalBusiness** (lihat di bawah).
- `id-wilayah.csv` — hierarki lengkap 91.162 wilayah (provinsi→kelurahan) untuk
  membuat CSV lokasi **asli** (bukan karangan AI).

### Ekspor CSV lokasi (bertahap = lebih sehat untuk SEO)
```bash
# Semua kelurahan Jawa Barat:
php artisan locations:export --province="Jawa Barat" --out=jabar.csv

# Kelurahan satu kota + kolom koordinat:
php artisan locations:export --city="Depok" --with-coords --out=depok.csv

# Level kecamatan saja (lebih sedikit halaman, lebih "berisi"):
php artisan locations:export --level=kecamatan --province="Bali" --out=bali-kec.csv
```
Header hasil: `kota,kecamatan,kelurahan` (+`lat,lng` bila `--with-coords`) —
langsung cocok untuk Import CSV.

**Rekomendasi:** mulai 1 provinsi/kota, indeks & ukur, baru meluas. Jangan
ekspor + publish 83.345 kelurahan sekaligus.

### Koordinat otomatis di schema
`RegionGeo` mencocokkan nama `kota` tiap halaman ke koordinat resmi. Jadi schema
`LocalBusiness.geo` terisi **otomatis** meski CSV tidak punya kolom lat/lng.
Bila baris CSV punya lat/lng sendiri, itu yang dipakai (lebih spesifik).

---

## File baru
```
app/Services/Ai/KeywordGenerator.php
app/Http/Controllers/Admin/KeywordController.php
app/Console/Commands/KeywordsGenerateCommand.php
app/Console/Commands/LocationsExportCommand.php
app/Services/RegionGeo.php
resources/views/admin/keywords/index.blade.php
database/data/id-region-coords.csv       # 551 baris (prov+kota, lat/lng)
database/data/id-wilayah.csv             # 91.162 wilayah (kode,nama)
```

## File diubah
```
app/Services/SeoService.php              # geo fallback via RegionGeo
routes/web.php                           # route keywords
resources/views/admin/layout.blade.php   # menu "Keyword AI"
```

Sumber data: cahyadsn/wilayah (basis data wilayah administrasi Indonesia,
berlisensi terbuka). Perbarui berkala bila ada pemekaran wilayah.
