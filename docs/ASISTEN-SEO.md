# Asisten SEO di Panel Admin

Menu baru: **🧠 Asisten SEO**. Ini bukan sekadar pengisi konten — ia berperan
sebagai **SEO Specialist senior** yang memegang situs Anda: tahu kondisinya,
bisa diajak diskusi, membuat laporan untuk owner, dan menjalankan tindakan.

## Apa yang bisa dilakukan

**1. Tahu kondisi situs (bukan menebak).**
Setiap jawaban disusun dari snapshot NYATA: stok variasi konten, jumlah halaman
published/draft, persentase halaman tipis, klik WhatsApp 7/30 hari, data Search
Console, dan kelengkapan konfigurasi. Ada **skor kesehatan 0–100** di atas
halaman, plus daftar **masalah terdeteksi** beserta dampak bisnisnya.

**2. Diskusi.** Tanya bebas, mis.:
- "Apa yang harus saya perbaiki duluan?"
- "Kenapa halaman saya belum dapat lead?"
- "Strategi 30 hari ke depan?"
- "Apakah aman kalau saya publish 1 juta halaman?"

**3. Laporan kesehatan untuk owner.** Tombol **📋 Buat Laporan Kesehatan**
menghasilkan laporan bahasa awam: ringkasan kondisi, yang sudah baik, masalah +
dampak ke bisnis, dan rekomendasi 30 hari berurutan prioritas.

**4. Eksekusi.** Bila perlu tindakan, asisten menampilkan **tombol aksi**:
isi variasi konten, buat keyword, panaskan cache, kirim ke IndexNow, atau
membuka halaman terkait.

## Keputusan desain penting: usulkan → konfirmasi

Asisten **tidak** mengeksekusi sendiri. Ia hanya boleh **mengusulkan** aksi dari
daftar tertutup (whitelist), dan Anda yang menekan tombol. Alasannya: tindakan
seperti publish massal berdampak besar dan sulit dibatalkan — keputusan akhir
harus tetap di tangan manusia. Aksi berisiko juga meminta konfirmasi ulang.

Asisten juga diinstruksikan **menolak taktik berisiko** (konten massal tanpa
nilai, review/rating palsu, cloicking, beli backlink) dan menjelaskan risikonya,
lalu menawarkan alternatif yang aman.


## Pembagian kerja: AI vs Owner

Prinsipnya: **AI mengerjakan yang bisa diotomatiskan, lalu melapor apa yang ia
butuhkan dari Anda.** Panel **🙋 Yang perlu Anda kerjakan sendiri** di halaman
asisten menampilkan daftar ini otomatis — lengkap dengan *kenapa AI tidak bisa*
dan *langkah konkretnya*.

Yang **hanya bisa owner** kerjakan:

| Kategori | Contoh | Alasan |
|---|---|---|
| Rahasia & server | kunci API, isi `.env`, `queue:work`, deploy | AI tidak punya akses shell/kredensial |
| Klaim kepemilikan | verifikasi Search Console | Google harus memastikan Anda pemilik domain |
| Fakta bisnis nyata | nomor WA, harga asli, testimoni riil, logo | AI tidak tahu — dan tidak boleh mengarang |
| Keputusan berisiko | persetujuan publish massal | Berdampak besar & sulit dibatalkan |

Asisten diinstruksikan **tidak boleh berpura-pura sudah mengerjakannya** dan
**tidak boleh diam** bila terhambat: ia harus menyebut blocker-nya secara terus
terang beserta cara mengatasinya.

---

## Jawaban: apakah CSV masih diperlukan?

**Ya, alur impor tetap dipakai — tapi Anda tidak perlu lagi membuat CSV manual.**

Perlu dibedakan dua hal:

| | Sumber | Peran AI |
|---|---|---|
| **Kalimat konten** (hero, USP, testimoni, FAQ) | Pool `content_blocks` | ✅ AI yang mengisi |
| **Keyword layanan** | Daftar layanan | ✅ AI yang membuat |
| **Nama wilayah** (kota/kecamatan/kelurahan) | Dataset resmi terbundel | ❌ **Bukan AI** |

Nama wilayah **sengaja tidak** diambil dari AI. Model bahasa bisa salah menulis
atau mengarang nama kelurahan, dan itu fatal untuk SEO lokal (halaman untuk
tempat yang tidak ada). Karena itu lokasi diambil dari dataset resmi 91.162
wilayah yang sudah terbundel.

**Alur tanpa menulis CSV manual sama sekali:**
```bash
# 1) Lokasi asli → CSV (otomatis, akurat)
php artisan locations:export --city="Depok" --with-coords --out=depok.csv
```
```
# 2) Keyword layanan → menu "Keyword AI" → Generate → Salin
# 3) Import CSV: unggah depok.csv + tempel keyword di "Daftar Layanan"
# 4) Generate → Publish
```
Jadi CSV tetap ada sebagai **jembatan data**, tapi isinya dihasilkan mesin —
Anda tidak mengetik satu baris pun.

**Satu hal yang tetap layak Anda isi sendiri:** kolom data lokal riil di CSV
(harga, jumlah tutor, landmark). Inilah pembeda nyata antar halaman dan
pertahanan terbaik terhadap penilaian "konten massal". AI bisa menulis kalimat,
tapi tidak tahu harga asli Anda.

---

## File
```
BARU     app/Services/Ai/SiteAuditService.php          # snapshot kondisi situs
BARU     app/Services/Ai/SeoAssistant.php              # otak asisten + whitelist aksi
BARU     app/Http/Controllers/Admin/AssistantController.php
BARU     resources/views/admin/assistant/index.blade.php
DIUBAH   routes/web.php                                # route assistant
DIUBAH   resources/views/admin/layout.blade.php        # menu "Asisten SEO"
```

Butuh `AI_API_KEY` terisi. Tanpa itu halaman tetap terbuka (skor & daftar masalah
tetap tampil karena dihitung lokal), hanya fitur diskusi/laporan yang nonaktif.
