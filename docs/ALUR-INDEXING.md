# Alur Kerja Indexing — Tanpa Boros Kuota

Panel **🔎 Indexing & Peringkat** kini bekerja dua tahap: tandai yang terindeks
memakai data gratis, lalu sisanya jadi daftar kerja untuk di-request.

## Tahap 1 — Tandai yang terindeks (TANPA kuota)

Tombol **🔄 Tandai Terindeks dari Data Pencarian**.

Halaman yang pernah muncul di hasil pencarian **pasti sudah terindeks**. Sistem
menarik daftar itu dari Search Analytics API (25.000 baris per panggilan, bisa
dipaginasi) dan menandai halaman yang cocok.

**Nol kuota inspeksi terpakai.** Bisa dijalankan sesering yang Anda mau.

## Tahap 2 — Sisanya jadi daftar kerja

Halaman published yang belum terbukti terindeks otomatis muncul di panel
**"Belum terindeks"** — terpaginasi 50 per halaman, diurutkan agar yang **belum
pernah diminta** tampil lebih dulu.

Tiap baris punya dua tombol:

| Tombol | Fungsi |
|---|---|
| **Minta Index ↗** | Membuka alat URL Inspection di Search Console (tekan *Request Indexing* di sana) — sekaligus mencatat tanggalnya |
| **Cek** | Inspeksi satu URL via API (memakai 1 kuota) untuk tahu alasan pastinya |

Ada penghitung **"Diminta hari ini: N / ~12"** karena Google membatasi
permintaan manual sekitar 10–12 URL per hari. Halaman yang sudah diminta diberi
tanda dan turun ke urutan bawah, jadi besok Anda lanjut ke yang berikutnya tanpa
mengulang.

---

## Kenapa tidak otomatis penuh?

Google **tidak menyediakan API** untuk meminta pengindeksan halaman umum.
Indexing API resmi hanya untuk `JobPosting` dan `BroadcastEvent`. Jadi langkah
"tekan Request Indexing" memang harus manusia — yang bisa diotomatiskan adalah
**menyiapkan antreannya** dan **mencatat progresnya**, dan itulah yang dilakukan
panel ini.

## Catatan kejujuran

Daftar "belum terindeks" bersifat **kandidat**, bukan kepastian. Halaman yang
sudah terindeks tetapi belum pernah muncul di pencarian akan ikut masuk daftar.
Gunakan tombol **Cek** untuk memastikan sebelum menghabiskan jatah request
manual pada halaman yang sebenarnya sudah aman.

## Urutan pemakaian yang disarankan

1. Publish halaman, tunggu 1–2 minggu.
2. Tekan **Tandai Terindeks dari Data Pencarian** (gratis).
3. Lihat daftar "belum terindeks".
4. Ambil 10–12 halaman **prioritas** (yang paling bernilai bisnis), tekan
   *Minta Index ↗*, tandai.
5. Ulangi tiap hari.
6. Sesekali jalankan **Periksa Status** mode sampel untuk tahu persentase
   keseluruhan dan alasan yang belum terindeks.

> Bila banyak halaman berstatus *"Discovered – currently not indexed"*,
> **berhenti meminta indexing**. Itu tanda Google menilai halaman kurang
> bernilai — memaksa request tidak akan menolong. Perkaya data lokal dulu.

## File
```
BARU    app/Jobs/SyncIndexFromAnalyticsJob.php
BARU    database/migrations/..._add_source_and_requested_to_index_statuses.php
DIUBAH  app/Http/Controllers/Admin/IndexingController.php  (daftar kerja + tandai)
DIUBAH  app/Services/SearchConsole/SearchConsoleService.php (kembalikan daftar URL)
DIUBAH  app/Models/PageIndexStatus.php
DIUBAH  resources/views/admin/indexing/index.blade.php
DIUBAH  routes/web.php
```

Setelah pasang:
```bash
php artisan migrate
php artisan route:clear && php artisan view:clear
```
