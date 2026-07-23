# 📄 Panduan Data CSV — Dari Google Suggest ke Ribuan Halaman

Mesin ini digerakkan oleh tiga jenis file CSV, semuanya bisa Anda tulis di
Google Sheets lalu di-download sebagai CSV:

| File | Isi | Di-upload di menu | Kolom |
|---|---|---|---|
| **1. Lokasi + Keyword** | Peta halaman yang akan dibuat | Import CSV | `kota,kecamatan,kelurahan` (+ opsional) |
| **2. Variasi Konten** | Stok kalimat penyusun halaman | Variasi Konten | `section,content,weight` |
| **3. FAQ** | Tanya-jawab | FAQ | `question,answer,layanan` |

Satu prinsip sebelum mulai: **satu baris file #1 = satu halaman jadi.** Salah
menyusun file #1 = salah ribuan halaman sekaligus. Karena itu bagian A adalah
bagian terpenting dokumen ini.

---

## A. Kata Kunci dari Google Suggest → Kolom `layanan`
### ⚠️ Aturan #1 yang menyelamatkan Anda dari kanibalisasi

Saat Anda mengetik "les privat" di Google, Suggest memuntahkan puluhan
variasi: *les privat matematika, les privat murah, les privat terdekat, guru
les privat, biaya les privat, les privat sd...* Godaan terbesar: memasukkan
SEMUA itu sebagai `layanan` berbeda. **Jangan.**

**Aturan emas: `layanan` = PRODUK/JASA yang benar-benar berbeda.
Kata sifat/modifier = bahan Variasi Konten, bukan halaman baru.**

| Keyword dari Suggest | Jenis | Perlakuan |
|---|---|---|
| les privat **matematika** | Produk berbeda | ✅ layanan sendiri |
| les privat **bahasa inggris** | Produk berbeda | ✅ layanan sendiri |
| les privat **SD** / **SMP** | Segmen berbeda | ✅ boleh layanan sendiri |
| les privat matematika **murah** | Modifier harga | ❌ → masukkan kata "murah/terjangkau" ke variasi hero & intro |
| les privat **terdekat** | Modifier lokasi | ❌ → sudah terjawab oleh halaman per-kelurahan itu sendiri |
| **biaya/harga** les privat | Modifier informasi | ❌ → jawab lewat kolom data lokal `harga` + FAQ "Berapa biayanya?" |
| **guru** les privat | Sinonim | ❌ → pakai sebagai variasi kalimat, bukan halaman baru |

Kenapa keras begini? Karena "les privat matematika" dan "les privat matematika
murah" di kelurahan yang sama = dua halaman 95% identik yang **saling rebutan
ranking** (kanibalisasi) dan memperbesar sinyal doorway. Satu halaman kuat yang
mengandung kata "murah", "terdekat", "biaya" di variasinya akan menangkap SEMUA
keyword itu sekaligus.

**Cara kerja yang benar dengan hasil Suggest:**
1. Kumpulkan semua suggest ke satu sheet (ketik keyword inti + a-z: "les privat a",
   "les privat b", ... — atau pakai tool suggest scraper).
2. Beri label tiap baris: `PRODUK` / `MODIFIER` / `PERTANYAAN`.
3. `PRODUK` → jadi daftar `layanan` (biasanya cuma 5–15 item!).
4. `MODIFIER` → jadi bahan menulis variasi hero/intro/cta (file #2).
5. `PERTANYAAN` (berapa biaya..., apakah bisa...) → jadi FAQ (file #3).

Dari ratusan keyword Suggest, output sehatnya: **±10 layanan, ±30 kalimat
variasi bermuatan modifier, ±10 FAQ.** Perkalian dengan lokasi-lah yang
membuatnya jadi ribuan halaman — bukan perkalian keyword.

---

## B. File #1: Lokasi + Keyword (mesin utama)

### B1. Sumber data wilayah — jangan mengarang nama

Gunakan data resmi **Kode Wilayah Kemendagri** (Permendagri terbaru) atau data
BPS — keduanya memuat seluruh provinsi → kota/kab → kecamatan → kelurahan/desa
se-Indonesia dan mudah dicari dalam bentuk CSV/spreadsheet siap pakai. Aturan
kebersihan data:

- **Konsisten**: pilih satu gaya dan pertahankan. "Bandung" ≠ "Kota Bandung" ≠
  "KOTA BANDUNG" — bagi mesin itu tiga kota berbeda (tiga slug URL berbeda).
  Saran: buang awalan "Kota/Kabupaten" dan pakai Title Case ("Bandung",
  "Tanah Sareal", "Kedung Badak").
- Hati-hati nama kembar antar-kota (banyak kelurahan bernama sama) — tidak
  masalah bagi mesin (path memuat kota+kecamatan), yang penting barisnya benar.
- Jangan ada sel kosong di kolom wajib.

### B2. Mode Cross-Join — "satu kali buat" yang Anda maksud

Anda TIDAK perlu meng-copy-paste blok lokasi untuk tiap keyword. Cukup:

1. Siapkan file lokasi **tanpa kolom layanan**: `kota,kecamatan,kelurahan`
   (+ kolom data lokal opsional).
2. Di form upload, isi kotak **"Daftar Layanan/Keyword"** — satu per baris:
   ```
   Les Privat Matematika
   Les Privat Bahasa Inggris
   Guru Ngaji ke Rumah
   ```
3. Mesin menggandakan otomatis: 500 lokasi × 8 layanan = 4.000 halaman
   dari SATU file. Kolom data lokal (harga dll.) ikut tersalin ke tiap
   kombinasi.

Kalau harga per layanan berbeda-beda, jangan pakai cross-join untuk kolom itu —
pakai CSV lengkap berkolom `layanan` agar harga bisa spesifik per baris. Boleh
juga dua tahap: cross-join dulu (tanpa harga), lalu upload ulang CSV berkolom
`layanan+harga` untuk memperkaya (mesin meng-update halaman yang sudah ada,
tanpa duplikat).

### B3. Strategi gelombang — persis rencana Anda, dengan satu penyesuaian

Rencana Anda benar arahnya: sebar tipis ke seluruh Indonesia dulu, lalu
dalami kota yang menunjukkan traffic. Satu penyesuaian penting: **salespage
mesin ini hidup di level kelurahan** (URL 4 segmen), sedangkan halaman
kota/kecamatan adalah halaman hub otomatis. Jadi "hadir di satu kota" =
punya beberapa halaman kelurahan di kota itu (hub kota otomatis ikut lahir
dan menangkap keyword "layanan + kota").

**Gelombang 1 — jejak tipis nasional (satu file):**
File: `gelombang1-nasional.csv` — untuk TIAP kota/kab di Indonesia, ambil
**hanya 2–3 kelurahan di kecamatan pusat kota** (alun-alun/pusat
pemerintahan). ±514 kota/kab × 2 kelurahan = ±1.000 lokasi; × 8 layanan
(cross-join) = ±8.000 halaman. Publish bertahap sesuai panduan operator.
Ini cukup untuk "memancing" sinyal: kota mana yang Google dan pasar merespons.

**Pantau 3–6 minggu:** GSC → Performance → filter halaman per kota (URL
mengandung `/bandung/`, `/bogor/`, dst.) + catat lead WA per kota.

**Gelombang 2 — hajar kota pemenang (satu file per kota):**
File: `gelombang2-bandung.csv` — SEMUA kecamatan + kelurahan kota itu.
Upload dengan daftar layanan yang sama. Baris yang sudah ada dari gelombang 1
otomatis dilewati (dedup). **Kunci operasional: satu file = satu batch =
bisa di-publish terpisah** — menu Halaman & Publish mendukung publish per
batch import, jadi gelombang 2 Bandung bisa jalan tanpa menyentuh draft lain.

**Gelombang 3+ —** ulangi per kota pemenang berikutnya; kota yang mati suri
tidak usah diperdalam.

Jangan import "seluruh kecamatan se-Indonesia" sebagai SATU file raksasa di
awal — secara mesin sanggup, tapi Anda kehilangan kendali publish per wilayah
dan melanggar prinsip bertahap.

---

## C. File #2: Variasi Konten (`section,content,weight`)

Tulis massal di Google Sheets, upload di menu **Variasi Konten → Import
Variasi CSV**. Kolom `weight` opsional (1–100, makin besar makin sering
terpilih). Section valid: `hero, intro, pain_point, solusi, usp, testimoni,
cta, about, summary_open, summary_bridge, summary_close, summary_filler`.

Token yang bisa dipakai dalam kalimat: `{{layanan}} {{kota}} {{kecamatan}}
{{kelurahan}} {{brand}}` + semua kolom data lokal Anda (`{{harga}}` dll.) +
khusus section summary: `{{usp_text}}`.

**Di sinilah keyword MODIFIER dari Suggest bekerja.** Contoh baris:

```
section,content,weight
hero,{{layanan}} Murah & Terpercaya di {{kelurahan}} — Bisa Mulai Hari Ini,1
hero,Cari {{layanan}} Terdekat dari Rumah Anda di {{kelurahan}}?,1
intro,Banyak warga {{kelurahan}} mencari {{layanan}} dengan biaya terjangkau tanpa mengorbankan kualitas — itulah yang {{brand}} tawarkan.,1
cta,Cek Biaya {{layanan}} di {{kelurahan}} — Gratis via WhatsApp,1
```

Duplikat persis otomatis dilewati, jadi aman upload ulang file yang direvisi.

## D. File #3: FAQ (`question,answer,layanan`)

Upload di menu **FAQ → Import FAQ CSV**. Kolom `layanan` kosong = FAQ global
(tampil di semua halaman); diisi nama layanan = FAQ khusus layanan itu.
Penting: **import file lokasi dulu** sebelum FAQ per-layanan, karena mesin
mencocokkan nama layanan dengan yang sudah terdaftar. Keyword `PERTANYAAN`
dari Suggest masuk ke sini:

```
question,answer,layanan
Berapa biaya {{layanan}} di {{kelurahan}}?,Biaya menyesuaikan kebutuhan — hubungi WhatsApp kami untuk rincian di wilayah Anda.,
Apakah tutor bisa datang ke rumah?,Bisa. Tutor kami melayani kunjungan ke rumah di seluruh {{kecamatan}}.,Les Privat Matematika
```

## E. Data Template — bukan CSV

Template adalah HTML, dikelola di menu **Template** (3 varian bawaan +
sidik jari tema otomatis). Tidak ada dan tidak perlu import CSV untuk ini.

---

## F. Teknis: Excel vs Google Sheets (sumber error #1 pemula)

- **Paling aman: Google Sheets** → File → Download → **Comma Separated
  Values (.csv)**. Encoding dan pemisah selalu benar.
- **Excel Indonesia** punya dua jebakan: menyimpan CSV dengan pemisah
  **titik-koma (;)** dan menyisipkan **BOM** tak terlihat. Mesin ini sudah
  saya buat toleran terhadap keduanya (auto-deteksi), tapi tetap: bila pakai
  Excel, pilih format **"CSV UTF-8 (Comma delimited)"**.
- Nama kolom huruf kecil, tanpa spasi (pakai `_`).
- Maksimal 50 MB per file (±500 ribu baris) — kalau lebih, pecah per provinsi.

## H. Contoh per Kategori Produk — Jasa Panggilan vs Produk Fisik

Kategori Anda campur dua jenis, dan keduanya **beda perlakuan** dalam CSV:

### H1. Jasa panggilan (service AC, las, sedot WC, cleaning, rental)
Orang mencari jasa ini dengan niat lokal kuat ("service AC terdekat", "las
panggilan jatiasih") — teknisi harus DATANG ke lokasi pelanggan. Ini medan
terkuat mesin: **kedalaman sampai kelurahan aman dan justru unggul.**

### H2. Produk fisik yang dikirim (herbal, alat pancing, deodoran)
Orang mencari "madu hutan asli" atau "jual alat pancing", jarang sekali
"madu hutan kelurahan kedung badak". Volume pencariannya hidup di level
KOTA, dan ribuan halaman kelurahan untuk produk kiriman adalah sinyal
doorway paling telanjang. **Aturan: untuk produk fisik, tahan kedalaman di
gaya "gelombang 1" secara PERMANEN — 2–3 kelurahan pusat per kota saja —
dan menangkan lewat sudut lokal yang nyata: COD, same-day, ambil di toko.**
Jangan pernah "hajar semua kelurahan" untuk produk kiriman.

### H3. Tabel pemecahan keyword per kategori Anda

| Kategori | ✅ Jadi `layanan` (produk berbeda) | ❌ Modifier → variasi/FAQ | Kedalaman | Kolom data lokal andalan |
|---|---|---|---|---|
| **Service AC** | Service AC, Cuci AC, Isi Freon AC, Pasang AC Baru, Bongkar Pasang AC | terdekat, murah, panggilan, 24 jam, harga | Kelurahan ✔ | harga, garansi, jam_operasional |
| **Las panggilan** | Las Panggilan, Las Pagar Besi, Las Kanopi, Las Teralis Jendela, Las Pintu Besi | terdekat, murah, per meter, borongan | Kelurahan ✔ | harga, pengalaman, garansi |
| **Rental mobil** | Rental Mobil Lepas Kunci, Rental Mobil dengan Sopir, Sewa Hiace, Sewa Avanza, Sewa Innova | murah, 24 jam, terdekat, harian/mingguan | Kota + kec. pusat | harga, stok, jam_operasional |
| **Herbal** | Madu Hutan Asli, Habbatussauda Kapsul, Minyak Zaitun EV (per PRODUK) | jual, asli, murah, manfaat, COD | Kelurahan pusat kota saja | harga, stok, izin_bpom, pengiriman |
| **Alat pancing** | Joran Pancing, Reel Pancing, Umpan & Aksesoris, Alat Pancing Lengkap | toko, jual, murah, terdekat, COD | Kelurahan pusat kota saja | harga, stok, pengiriman, garansi |
| **Penghilang bau badan** | Penghilang Bau Badan Herbal, Deodoran Tawas Alami, Spray Anti Bau (per VARIAN produk) | ampuh, permanen, alami, murah | Kelurahan pusat kota saja | harga, stok, izin_bpom, pengiriman |

Perhatikan dua pola penting di tabel:
- **"Jual", "toko", "agen", "distributor" adalah MODIFIER, bukan layanan.**
  `layanan` diisi kata benda produknya ("Madu Hutan Asli") supaya kalimat
  variasi tetap enak dibaca ("{{brand}} menyediakan {{layanan}}..."); kata
  "jual/toko" masuk ke variasi hero: "Jual {{layanan}} di {{kelurahan}} — COD".
- **Kategori niche kecil punya layanan sedikit — dan itu BAGUS.** Penghilang
  bau badan cuma 2–3 varian produk = layanan sedikit × lokasi = tetap ribuan
  halaman yang masing-masing bermakna, bukan puluhan ribu halaman kosong.

File contoh siap edit untuk keenam kategori ada di `docs/contoh-csv/`
(contoh-service-ac.csv, contoh-las-panggilan.csv, contoh-rental-mobil.csv,
contoh-herbal.csv, contoh-alat-pancing.csv, contoh-penghilang-bau-badan.csv).
Ingat aturan yang sudah berlaku: **satu niche = satu domain** — enam kategori
ini adalah enam website terpisah, masing-masing dengan Paket Konten,
brand, dan file CSV-nya sendiri.

---

## G. Checklist sebelum menekan Upload

1. ☐ Kolom `layanan` (atau Daftar Layanan) hanya berisi PRODUK berbeda —
   tidak ada "murah/terdekat/terbaik/harga" di dalamnya
2. ☐ Ejaan kota/kecamatan/kelurahan konsisten satu gaya
3. ☐ Tidak ada sel kosong di kolom wajib
4. ☐ File dari Google Sheets, atau Excel "CSV UTF-8"
5. ☐ Ini file gelombang berapa? Sesuai strategi B3? (bukan file raksasa nasional)
6. ☐ Sesudah generate: cek 10 halaman sampel sebelum publish (panduan operator, Langkah 3)
