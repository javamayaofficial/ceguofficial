# 📘 Panduan Operator CEGU — Untuk Orang Awam
### Menjalankan mesin salespage tanpa perlu bisa coding

Panduan ini ditulis untuk operator yang **tidak punya latar belakang IT**.
Semua pekerjaan di sini dilakukan lewat browser (Chrome/Firefox), cukup
klik-klik seperti menggunakan Facebook atau Tokopedia Seller Center.

---

## Bagian 1 — Kenali Dulu Mesinnya (5 menit)

Bayangkan mesin ini seperti **pabrik brosur otomatis**:

1. Anda setor **daftar lokasi** (file Excel/CSV berisi: layanan, kota,
   kecamatan, kelurahan).
2. Mesin **mencetak satu halaman jualan** untuk setiap baris — lengkap dengan
   judul, isi, tombol WhatsApp, dan "bumbu SEO" agar mudah ditemukan Google.
3. Anda tinggal menekan tombol **Publish** untuk menayangkannya.

Satu hal terpenting yang harus Anda pahami: **Google tidak suka pabrik yang
mencetak terlalu cepat.** Maka pekerjaan Anda sebagai operator bukan sekadar
menekan tombol, tapi **mengatur ritme** — seperti menyiram tanaman: rutin dan
bertahap, bukan diguyur sekaligus.

### Yang BISA Anda lakukan sendiri (tanpa teknisi)
✅ Upload daftar lokasi dan membuat halaman
✅ Menayangkan (publish) dan menyembunyikan halaman
✅ Menambah/mengubah kalimat-kalimat di halaman (variasi konten)
✅ Menambah/mengubah Tanya-Jawab (FAQ)
✅ Mengganti nomor WhatsApp, nama brand, gambar
✅ Memantau performa di Google Search Console

### Yang perlu BANTUAN TEKNISI (sekali di awal, atau saat error)
🔧 Pemasangan awal di server (setengah hari kerja)
🔧 Mengubah desain/tata letak halaman (template HTML)
🔧 Bila website mati total atau muncul pesan error aneh

---

## Bagian 2 — Masuk ke Ruang Kendali

1. Buka browser, ketik: `https://domain-anda.com/admin`
2. Masukkan email & password yang diberikan teknisi.
3. Anda akan melihat **Dashboard** — papan skor berisi: total halaman, berapa
   yang masih Draft (belum tayang), berapa yang Published (sudah tayang),
   berapa yang gagal.

**Menu yang akan sering Anda pakai:**

| Menu | Fungsinya, dalam bahasa sederhana |
|---|---|
| **Dashboard** | Papan skor: kondisi pabrik hari ini |
| **Import CSV** | Setor daftar lokasi → mesin membuat halaman |
| **Halaman & Publish** | Menayangkan / menyembunyikan halaman |
| **Variasi Konten** | Gudang kalimat — makin banyak, makin unik tiap halaman |
| **FAQ** | Tanya-jawab yang muncul di halaman |
| **Pengaturan** | Nomor WA, nama brand, gambar |
| **Template** | Desain halaman — ⚠️ JANGAN diutak-atik tanpa teknisi |

---

## Bagian 3 — Siapkan File Daftar Lokasi (CSV)

CSV itu file Excel versi sederhana. Cara membuatnya:

1. Buka **Excel** atau **Google Sheets**.
2. Baris pertama tulis persis: `layanan,kota,kecamatan,kelurahan`
   (satu kata per kolom).
3. Isi baris berikutnya, satu baris = satu halaman jadi. Contoh:

   | layanan | kota | kecamatan | kelurahan |
   |---|---|---|---|
   | Les Privat Matematika | Bandung | Cicendo | Pajajaran |
   | Guru Ngaji | Bekasi | Jatiasih | Jatikramat |

4. **(NAIK LEVEL — sangat disarankan)** Tambahkan kolom data lokal. Kolom apa
   pun di luar 4 kolom wajib otomatis menjadi "fakta lokal" halaman itu:

   | layanan | kota | kecamatan | kelurahan | harga | jumlah_tutor | landmark | sekolah |
   |---|---|---|---|---|---|---|---|
   | Les Privat Fisika | Bandung | Coblong | Dago | Rp75.000/sesi | 12 | Kampus ITB | SMAN 1 Bandung |

   Efeknya besar: fakta ini tampil sebagai blok "Fakta Lokal" di halaman,
   dianyam ke ringkasan otomatis, dan **membuat deskripsi Google tiap halaman
   benar-benar berbeda** — inilah pembeda halaman yang diterima vs ditolak
   Google. Nama kolom bebas (huruf kecil, tanpa spasi — pakai `_`), tapi 4
   nama ini mendapat perlakuan istimewa: `harga`, `jumlah_tutor`, `landmark`,
   `sekolah`.

   💡 **Belum punya datanya sekarang?** Tidak masalah. Upload dulu tanpa kolom
   tambahan; nanti kalau datanya siap, upload ulang file yang sama + kolom
   baru — mesin otomatis **memperkaya halaman yang sudah ada** tanpa membuat
   duplikat.

5. Simpan sebagai **CSV**: di Excel → *File → Save As → CSV (Comma delimited)*;
   di Google Sheets → *File → Download → Comma Separated Values (.csv)*.

**Aturan penulisan:**
- Ejaan harus konsisten. "Bandung" dan "Kota Bandung" dianggap DUA kota berbeda.
- Tidak apa-apa ada baris kembar — mesin otomatis melewatinya.
- Jangan ada kolom kosong.

💡 Baris yang sama tidak akan pernah membuat halaman ganda, jadi aman
meng-upload ulang file yang sama.

---

## Bagian 3B — Dashboard Sekarang Memandu Anda

Buka **Dashboard** dan Anda akan melihat dua panel baru yang bekerja otomatis:

**🚀 "Mulai dari sini"** — checklist 5 langkah yang MENCENTANG DIRINYA SENDIRI
saat Anda menyelesaikan tiap tahap (isi pengaturan → penuhi stok → import →
publish → daftar Google). Tiap langkah ada tombol "kerjakan →" yang membawa
Anda langsung ke menu yang tepat. Panel ini hilang sendiri setelah semua
selesai. Kalau bingung harus ngapain: buka Dashboard, ikuti checklist. Titik.

**Kesehatan Stok Konten** — bar berwarna untuk tiap jenis kalimat:
- 🟢 Hijau = stok cukup.
- 🟠 Oranye = kurang; angka `12/20` artinya baru 12 dari target 20.
- Label besar di atasnya menjawab langsung: **"SIAP GENERATE MASSAL"** atau
  **"TAMBAH STOK DULU"**. Selama masih merah, JANGAN menayangkan halaman
  dalam jumlah besar — mesin sedang melindungi Anda dari penalti Google.

---

## Bagian 4 — Alur Kerja Inti (4 langkah)

```
1. IMPORT  →  2. TUNGGU MESIN  →  3. CEK SAMPEL  →  4. PUBLISH BERTAHAP
```

### Langkah 1 — Import
Menu **Import CSV** → pilih file → klik **Upload**. Selesai. Mesin mulai
bekerja di belakang layar.

### Langkah 2 — Tunggu mesin (sambil ngopi ☕)
Di halaman yang sama ada **bar progres** yang jalan sendiri. Ada tombol
**Pause** (jeda) dan **Resume** (lanjut) kalau perlu. Ribuan baris biasanya
selesai dalam hitungan menit. Semua halaman baru berstatus **Draft** —
artinya SUDAH JADI tapi BELUM TAYANG. Ini disengaja dan aman.

### Langkah 3 — Cek sampel (WAJIB, jangan dilewati)
Sebelum menayangkan, buka menu **Halaman & Publish**, klik 5–10 halaman
secara acak, lalu periksa dengan checklist ini:

- [ ] Nama kelurahan/kecamatan/kota tertulis benar (tidak typo)?
- [ ] Kalimat-kalimatnya enak dibaca, tidak janggal?
- [ ] Tombol WhatsApp bila diklik → membuka chat ke nomor yang benar,
      dengan pesan yang menyebut layanan & lokasi yang benar?
- [ ] Tampilan rapi di HP (buka dari HP Anda)?

Ada yang salah? Perbaiki dulu (biasanya di **Pengaturan** atau
**Variasi Konten**) sebelum lanjut.

### Langkah 4 — Publish BERTAHAP ⚠️ (bagian terpenting panduan ini)
Menu **Halaman & Publish** → tombol **Mulai Publish**. Gunakan **Pause**
untuk menghentikan sesuai jatah. **Aturan ritme yang aman:**

| Fase | Berapa banyak yang ditayangkan | Lalu apa |
|---|---|---|
| Bulan pertama | 1 kota saja (±500–2.000 halaman) | Tunggu & pantau 2–4 minggu |
| Kalau hasil bagus | +10.000–50.000 halaman per MINGGU | Terus pantau |
| Kalau hasil jelek | ⛔ STOP menambah | Perbaiki konten dulu (Bagian 6) |

**"Bagus" atau "jelek" dilihat dari mana?** Dari Google Search Console
(Bagian 7). Patokan sederhana: dari halaman yang sudah tayang, kalau lebih
dari separuh diterima Google → bagus, lanjut. Kalau kurang dari sepertiga
→ jelek, berhenti dulu.

> 🚫 **PANTANGAN #1: jangan pernah menayangkan ratusan ribu/jutaan halaman
> sekaligus.** Ini seperti membuka 2 juta toko dalam semalam — Google akan
> curiga dan bisa "memblokir" seluruh website Anda. Kalau itu terjadi,
> memperbaikinya jauh lebih sulit daripada mencegahnya.

---

## Bagian 4B — Mesin Ini untuk Bisnis Apa Pun (Paket Konten)

Mesin ini **universal** — bisa dipakai untuk les privat, servis AC, sedot WC,
jual herbal, jual rumah, kavling, katering, dan apa pun yang dijual
per-wilayah. Cara pindah/mulai niche:

1. Buka menu **Variasi Konten** → di paling atas ada kotak hijau
   **"🧰 Paket Konten Awal"**.
2. Pilih jenis bisnis Anda: **Jasa Umum**, **Produk Herbal & Kesehatan**,
   **Properti**, atau **Pendidikan** → klik **Muat Paket**.
3. Dalam sekali klik, puluhan kalimat + FAQ yang sesuai bisnis itu langsung
   terisi. Variasi lama TIDAK dihapus — kalau benar-benar ganti bisnis,
   hapus kalimat lama secara manual dulu agar tidak tercampur.
4. Buka **Pengaturan** → isi **Nama Brand** dan **Tagline** sesuai bisnis
   Anda (mis. "Herbal asli, kirim cepat ke seluruh kota"). Tagline tampil di
   beranda dan footer semua halaman.
5. Sesuaikan & perbanyak kalimat paket agar terasa seperti brand Anda sendiri
   — paket hanyalah titik awal.

Kolom CSV data lokalnya pun mengikuti bisnis Anda. Contoh per niche:
- **Jasa**: `harga`, `garansi`, `jam_operasional`, `pengalaman`
- **Herbal/produk**: `harga`, `stok`, `izin_bpom`, `isi`, `pengiriman`
- **Properti**: `harga`, `luas_tanah`, `luas_bangunan`, `kamar`, `legalitas`
Semua otomatis tampil rapi di blok Fakta Lokal dan dianyam ke ringkasan.

---

## Bagian 5 — Gudang Kalimat (Variasi Konten)

Ini rahasia kenapa 1.000 halaman tidak terlihat kembar. Mesin merangkai
setiap halaman dari **stok kalimat** yang Anda isi di menu **Variasi Konten**.

Analogi: seperti warung nasi campur. Kalau lauknya cuma 4 macam, semua
pelanggan dapat piring yang mirip. Kalau lauknya 25 macam, tiap piring beda.

**Tugas rutin Anda: memperbanyak stok kalimat.** Target minimal sebelum
menayangkan banyak halaman:

| Bagian halaman | Artinya | Stok minimal |
|---|---|---|
| Hero | Judul besar paling atas | 20 |
| Intro | Paragraf pembuka | 20 |
| Pain Point | Masalah yang dirasakan calon pelanggan | 15 |
| Solusi | Jawaban atas masalah itu | 15 |
| USP | Keunggulan Anda | 15 |
| Testimoni | Ulasan pelanggan | 25 (nama & cerita berbeda-beda) |
| CTA | Ajakan menghubungi | 10 |
| Summary: pembuka/jembatan/penutup | Kalimat perangkai ringkasan otomatis | 8 / 8 / 8 |

**Trik menulis variasi:** selipkan kode `{{kelurahan}}`, `{{kota}}`,
`{{layanan}}` di kalimat Anda — mesin otomatis menggantinya dengan nama asli
di tiap halaman. Contoh:

> Ketik: `Banyak orang tua di {{kelurahan}} kesulitan mencari {{layanan}} yang terpercaya.`
> Jadi: *"Banyak orang tua di Pajajaran kesulitan mencari Les Privat Matematika yang terpercaya."*

Menambah variasi kapan saja **aman** — semua halaman (termasuk yang sudah
tayang) otomatis ikut segar tanpa perlu diapa-apakan.

**FAQ juga bagian dari ini.** Di menu **FAQ**, buat 10 tanya-jawab umum +
3–5 tanya-jawab khusus per layanan (mis. FAQ khusus "Guru Ngaji" beda dengan
FAQ "Les Matematika"). Ini pembeda besar di mata Google.

---

## Bagian 6 — Kalau Hasil Jelek, Perbaiki Ini

Google menolak banyak halaman? Jangan panik, jangan tambah halaman. Lakukan
berurutan:

1. **Gandakan stok kalimat** di Variasi Konten (target 2× lipat tabel di atas).
2. **Tambah FAQ per layanan** — minimal 5 per layanan.
3. **Tulis testimoni yang lebih hidup** — sebut nama depan, kelas anak,
   hasil nyata ("nilai naik dari 60 ke 85").
4. Tunggu 2–3 minggu, pantau lagi. Membaik → lanjut bertahap.

---

## Bagian 7 — Memantau di Google Search Console (GSC)

GSC = rapor gratis dari Google. Teknisi memasangnya sekali di awal; setelah
itu Anda cukup **membaca**, seminggu sekali:

1. Buka https://search.google.com/search-console → pilih website Anda.
2. Klik **Pengindeksan → Halaman** (Indexing → Pages).
3. Lihat dua angka:
   - **Terindeks** = halaman yang DITERIMA Google ✅
   - **Di-crawl, saat ini tidak diindeks** = dilihat tapi DITOLAK ❌
4. Hitung kasar: Terindeks dibanding total yang Anda tayangkan.
   - Lebih dari separuh → sehat, boleh nambah.
   - Kurang dari sepertiga → berhenti, kerjakan Bagian 6.

Satu lagi yang wajib dicek: menu **Tindakan Manual** (Manual Actions).
Harus selalu bertuliskan "Tidak ada masalah". Kalau ada isi → screenshot,
kirim ke teknisi HARI ITU JUGA.

---

## Bagian 8 — Rutinitas Operator

**Harian (5 menit):**
- Buka Dashboard: adakah angka "gagal" yang naik? Website bisa dibuka?
- Cek WhatsApp: ada lead masuk? (Ini ukuran sukses yang sebenarnya!)

**Mingguan (30 menit):**
- Baca rapor GSC (Bagian 7), catat angkanya di buku/spreadsheet.
- Tambah 5–10 kalimat baru di Variasi Konten.
- Kalau rapor sehat: publish gelombang berikutnya sesuai ritme.

**Bulanan (1 jam):**
- Cek 10 halaman acak seperti checklist Langkah 3.
- Evaluasi: kota mana yang menghasilkan lead? Prioritaskan layanan/kota
  serupa di gelombang berikutnya.

---

## Bagian 9 — Kamus Istilah

| Istilah | Artinya |
|---|---|
| Draft | Halaman sudah jadi tapi belum tayang (belum bisa dilihat publik) |
| Published | Halaman sudah tayang & didaftarkan ke Google |
| Import | Menyetor file daftar lokasi |
| Generate | Proses mesin membuat halaman dari daftar itu |
| CSV | File Excel versi sederhana |
| Sitemap | "Daftar isi" website yang dibaca Google — otomatis, tak perlu diurus |
| Terindeks | Halaman diterima & bisa muncul di pencarian Google |
| Lead | Calon pelanggan yang menghubungi via WhatsApp |

---

## Bagian 10 — Kapan Harus Memanggil Teknisi

Hubungi teknisi bila:
- Website tidak bisa dibuka / muncul tulisan "500 Server Error".
- Bar progres import/publish **diam lebih dari 1 jam** padahal belum selesai
  (biasanya "mesin belakang layar"/worker mati — teknisi menyalakan ulang
  dalam 5 menit).
- GSC menunjukkan **Tindakan Manual**.
- Anda ingin mengubah desain/tata letak halaman.

Kalimat ajaib untuk teknisi: *"Tolong cek apakah queue worker jalan, dan
lihat log error Laravel."* — mereka akan langsung paham.

---

## Penutup — 5 Aturan Emas

1. **Bertahap, bukan sekaligus.** Ritme mengalahkan volume.
2. **Cek sampel sebelum publish.** 10 menit ini menyelamatkan ribuan halaman.
3. **Stok kalimat adalah bahan bakar.** Rajin menambah = halaman makin unik.
4. **Rapor GSC menentukan gas atau rem.** Jangan menebak; baca angkanya.
5. **Ukuran sukses = lead WhatsApp masuk**, bukan jumlah halaman.

Selamat mengoperasikan! 🚀
