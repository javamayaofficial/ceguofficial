---
judul: Cara Kerja Mesin CEGU (alur & batasan)
pemicu: cara, alur, import, csv, generate, publish, template, variasi, indikator, hijau, worker, antrian, queue, cache, layanan, keyword
selalu: false
prioritas: 3
---
# Cara Kerja Mesin (rujukan operasional)

## Tiga jenis data yang berbeda
1. Variasi Konten + FAQ = stok KALIMAT (global, mengisi semua halaman). Ini yang
   membuat indikator kesehatan hijau. TARGET: hero 20, intro 20, pain_point 15,
   solusi 15, usp 15, testimoni 25, cta 10, about 5, summary_open/bridge/close 8,
   summary_filler 4, FAQ 10.
2. CSV lokasi = menghasilkan HALAMAN (URL). Tidak memengaruhi indikator.
3. Pengaturan = brand, WhatsApp, logo (dipakai semua halaman).

## Alur produksi
Isi variasi (hijau) -> isi FAQ -> import CSV lokasi + isi "Daftar Layanan" ->
Generate -> Publish bertahap.

## Batasan penting
- Semua proses berat lewat QUEUE. Tanpa `queue:work` berjalan, tombol hanya
  mengantre dan tak pernah selesai.
- Nama layanan: 2-4 kata, TANPA nama kota (kota ditambah otomatis saat cross-join).
- 1 layanan x N lokasi = N halaman. Hati-hati pengali.
- Template hanya mengatur SALESPAGE, bukan beranda/hub.
- Ganti .env harus `php artisan config:clear`. Ganti template TIDAK perlu (otomatis).

## Token data lokal yang didukung (isi lewat kolom CSV extra)
harga, jam_operasional, jadwal, landmark, garansi, legalitas, jumlah_tutor,
sekolah, stok, pengiriman, izin_bpom, komposisi, kamar, luas_tanah, luas_bangunan.

## Token gambar (isi lewat Pengaturan, bukan CSV)
{{hero_image}} {{gambar_keunggulan}} {{gambar_solusi}} {{gambar_proses}}
{{gambar_tentang}} {{galeri}}. Alt text otomatis berbeda tiap halaman (menyebut
layanan + lokasi), jadi satu gambar boleh dipakai di semua halaman.

## Halaman statis (menu navigasi)
Menu Admin "Halaman Statis" membuat halaman ber-URL sendiri (Tentang, Layanan,
Kontak). Muncul otomatis di navigasi & footer, dan sudah masuk sitemap
(/sitemap-statis.xml). Ada paket siap pakai per jenis usaha (jasa, produk,
properti, pendidikan, kesehatan).
Token khusus halaman statis: {{gambar1}}..{{gambar4}}, {{galeri}}, dan
{{situs_tentang}} / {{situs_keunggulan}} / {{situs_solusi}} / {{situs_proses}} /
{{situs_hero}} / {{situs_galeri}} untuk memakai ulang gambar situs.

## Warna & identitas
Warna situs diatur di Pengaturan (6 kolom HEX). Struktur salespage SERAGAM untuk
semua produk — yang membedakan hanya warna, logo, gambar, dan isi pool konten.

## Panel pemantauan
- "Lead WhatsApp": klik hari ini / bulan / tahun, sebaran per nomor CS (rotator).
  Yang dihitung KLIK, bukan chat terkirim.
- "Indexing & Peringkat": terindeks vs belum, alasan dari Google, sebaran posisi.
  Kuota inspeksi 2.000/hari per properti — pakai "Tandai dari Data Pencarian"
  (tanpa kuota) untuk situs besar.
