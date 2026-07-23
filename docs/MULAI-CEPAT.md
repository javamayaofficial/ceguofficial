# 🚀 Mulai Cepat — Satu Kata Kunci, Semua Terisi

Menu baru **Mulai Cepat** di panel admin. Isi kata kunci yang dibidik, tekan satu
tombol — AI (dibimbing otak MD) menulis seluruh variasi konten, FAQ, dan
menyiapkan halaman sampai indikator hijau.

## Apa yang dikerjakan otomatis

| Tahap | Isi | Sumber |
|---|---|---|
| 1 | 153 variasi konten (hero, intro, USP, dst.) sampai target | AI + otak MD |
| 2 | 10 FAQ | AI + otak MD |
| 3 | Baris lokasi (kota/kecamatan/kelurahan) | **Dataset resmi**, bukan AI |
| 4 | Halaman dibuat (status **DRAFT**) | Mesin |

Otak MD kini ikut membimbing penulisan: aturan gaya bahasa (`brand-voice`)
selalu disertakan, dan pengetahuan bidang (mis. `niche-les-privat`) disuntik
bila relevan — sehingga kalimat yang dihasilkan konsisten dengan aturan bisnis
Anda, bukan generik.

## Yang TIDAK otomatis (dan alasannya)

**Nama wilayah tidak dibuat AI.** Model bahasa bisa salah menulis atau mengarang
nama kelurahan — fatal untuk SEO lokal. Karena itu lokasi diambil dari dataset
resmi 91.162 wilayah; Anda cukup **memilih** provinsi + kota dari dropdown.
Terverifikasi: 514 nama kota unik, tidak ada tabrakan URL antara "Bogor" (kota)
dan "Kabupaten Bogor".

**Publish tidak otomatis.** Halaman berhenti di status DRAFT. Menerbitkan ribuan
halaman berdampak besar dan sulit dibatalkan — periksa 5–10 sampel dulu, baru
publish bertahap.

**Data lokal harus Anda isi.** Form menyediakan kolom harga, jam operasional,
dan hari operasional. AI tidak tahu angka riil bisnis Anda, dan mengarang harga
yang tayang ke calon pelanggan itu merugikan Anda sendiri. Kolom ini juga yang
membuat halaman lolos ambang "tidak tipis".

## Soal testimoni — baca sebelum mencentang

Ada opsi "Ikut isi testimoni dengan AI (25 variasi)". Testimoni buatan AI
**bukan** pengalaman pelanggan nyata. Menayangkannya seolah asli itu menyesatkan
dan melanggar kebijakan Google.

Dua pilihan jujur:
- **Centang** → indikator langsung hijau, tapi perlakukan sebagai pengisi
  sementara dan ganti dengan kutipan asli sebelum publish.
- **Hapus centang** → indikator testimoni belum penuh, tapi situs Anda jujur
  sejak awal. Kumpulkan 10–15 testimoni asli lewat WhatsApp; itu jauh lebih
  bernilai daripada 25 karangan.

## Cara pakai

1. Buka **Admin → 🚀 Mulai Cepat**.
2. Isi kata kunci utama (2–4 kata, **tanpa nama kota**).
3. Pilih provinsi → kota, dan tingkat halaman (kelurahan/kecamatan).
4. Isi data lokal (harga, jam, hari).
5. Tentukan pilihan testimoni.
6. Tekan **Jalankan**.

Progres tampil real-time. Pastikan `php artisan queue:work` berjalan — tanpa
worker, proses hanya mengantre.

## Perhatikan pengalinya

Tiap kata kunci dikalikan seluruh lokasi:

| Kata kunci | Depok (63 kel.) | Kota besar (~250 kel.) |
|---|---|---|
| 1 | 63 halaman | 250 halaman |
| 4 | 252 halaman | 1.000 halaman |

Untuk percobaan pertama: **satu kata kunci, satu kota, tingkat kecamatan** —
cepat selesai dan mudah diperiksa.

## File
```
BARU     app/Jobs/QuickStartJob.php                        (orkestrator 4 tahap)
BARU     app/Http/Controllers/Admin/QuickStartController.php
BARU     app/Support/RegionDataset.php                     (akses dataset wilayah)
BARU     resources/views/admin/quickstart/index.blade.php
DIUBAH   app/Services/Ai/AiContentGenerator.php            (otak MD membimbing penulisan)
DIUBAH   routes/web.php
DIUBAH   resources/views/admin/layout.blade.php            (menu Mulai Cepat)
```

Setelah pasang: `php artisan route:clear && php artisan view:clear`
