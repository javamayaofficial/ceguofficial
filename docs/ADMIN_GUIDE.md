# Panduan Penggunaan Admin — CEGU pSEO Engine

Panel admin: `https://domain-anda.com/admin` (login dulu).
Semua tugas berikut **tidak memerlukan programmer**.

---

## 1. Alur Kerja Utama

```
Upload CSV  →  Generate (otomatis, draft)  →  Review  →  Publish  →  Masuk Sitemap
```

1. **Import CSV** → halaman dibuat berstatus **Draft**.
2. **Review** di menu *Halaman & Publish*.
3. **Publish** → halaman tayang & masuk `sitemap.xml`.

> Pastikan **queue worker aktif** (`php artisan queue:work` / Supervisor),
> karena generate & publish berjalan di background.

---

## 2. Import CSV & Generate (menu *Import CSV*)

- Format kolom (baris pertama wajib header): `layanan,kota,kecamatan,kelurahan`
- Contoh:
  ```csv
  layanan,kota,kecamatan,kelurahan
  Les Privat Matematika,Bandung,Cicendo,Pajajaran
  Guru Ngaji,Bekasi,Jatiasih,Jatikramat
  ```
- Upload → sistem otomatis membuat URL `/{layanan}/{kota}/{kecamatan}/{kelurahan}`
  (mis. `/les-privat-matematika/bandung/cicendo/pajajaran`).
- **Start/Pause/Resume/Status**: progres tampil real-time. Klik **Pause** untuk
  menjeda, **Resume** untuk lanjut. Aman untuk jutaan baris (tidak timeout).
- Baris duplikat otomatis dilewati (tidak menggandakan halaman).

## 3. Publish (menu *Halaman & Publish*)

- **Mulai Publish**: publish semua draft, atau pilih per batch import.
- **Pause / Resume** publish kapan saja (mengontrol kecepatan publikasi).
- Bisa juga **Publish / Draft-kan** satu halaman secara manual.
- Hanya halaman **Published** yang muncul di `sitemap.xml`.

## 4. Template Salespage (menu *Template*)

- **Satu template aktif dipakai SEMUA halaman.** Mengubahnya → semua halaman ikut berubah.
- Editor mendukung **HTML / CSS / JavaScript** (dan Blade bila diaktifkan di Pengaturan),
  lengkap dengan **syntax highlighting** dan tombol **Preview** (membuka contoh halaman).
- **Token dinamis** (klik untuk menyalin di editor):

  | Token | Hasil |
  |---|---|
  | `{{layanan}}` `{{kota}}` `{{kecamatan}}` `{{kelurahan}}` | Nama dari data |
  | `{{brand}}` | Nama brand (Pengaturan) |
  | `{{wa}}` / `{{wa_button}}` | Link / tombol WhatsApp |
  | `{{hero}}` `{{intro}}` `{{about}}` `{{cta}}` | Variasi konten (1 terpilih) |
  | `{{pain_point_list}}` `{{solusi_list}}` `{{usp_list}}` `{{testimoni_list}}` | Daftar variasi |
  | `{{faq}}` | Blok FAQ + schema |
  | `{{summary}}` | Ringkasan otomatis 80–150 kata |
  | `{{breadcrumb}}` `{{internal_links}}` | Navigasi & internal link otomatis |

- Buat beberapa template, lalu klik **Aktifkan** pada yang dipakai.

## 5. Variasi Konten (menu *Variasi Konten*)

- Inilah "mesin keunikan" (Formula Kombinasi). Tiap section (Hero, Intro, Pain Point,
  Solusi, USP, Testimoni, CTA, About) punya banyak variasi.
- Tiap halaman memilih variasi secara **deterministik** → terlihat unik namun stabil.
- **Makin banyak variasi yang Anda tambahkan, makin banyak kombinasi halaman unik.**
- Boleh memakai token (mis. `Les Privat {{layanan}} di {{kelurahan}}`).
- **Bobot**: angka lebih besar = peluang muncul lebih sering.

## 6. FAQ (menu *FAQ*)

- FAQ **Global** (berlaku semua layanan) atau **per layanan**.
- Mendukung token. Otomatis menghasilkan **FAQ Schema** di halaman.

## 7. Pengaturan (menu *Pengaturan*)

- **Nomor WhatsApp** + **pesan otomatis** (boleh pakai token) → dipakai semua CTA.
- **Brand & Organization** → untuk judul & **Organization Schema**.
- **Aktifkan Blade**: hanya jika Anda paham Blade (`@if`, `@foreach`).

## 8. SEO yang dihasilkan otomatis (tiap halaman)

Meta Title, Meta Description, Canonical, H1, Breadcrumb, dan **Schema JSON-LD**
(Breadcrumb + FAQ + Organization). Tidak perlu setting manual per halaman.

## 9. Sitemap & Google

- `https://domain-anda.com/sitemap.xml` (maks 50.000 URL/file; otomatis jadi index bila lebih).
- Submit URL tersebut ke **Google Search Console**.

---

## Operasi via Terminal (opsional)

```bash
php artisan cegu:import path/ke/file.csv   # import + generate dari CLI
php artisan cegu:publish                   # mulai publish semua draft
php artisan cegu:publish --batch=3         # publish batch tertentu
php artisan cegu:stats                     # ringkasan jumlah halaman
```

## Tips
- Selalu pastikan **worker** aktif agar generate/publish berjalan.
- Setelah mengubah template/variasi/FAQ, cache otomatis di-refresh.
- Mulai dari ratusan halaman dulu, pantau di Google, lalu tambah skala.
