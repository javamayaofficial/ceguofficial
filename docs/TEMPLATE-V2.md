# Upgrade Template Salespage

Dua versi tersedia:
- **`database/data/template-cegu-v3.html`** — **DISARANKAN**. Mengacu desain
  beranda cegu.my.id (urutan section & gaya eyebrow sama), token tidak berubah.
- `database/data/template-cegu-v2.html` — versi generik (niche apa pun).

## v3 — mengacu beranda cegu.my.id

Urutan section mengikuti beranda: Hero → Keunggulan Kami → Tantangan → Solusi →
Rincian Program → Cara Memesan → Apa Kata Mereka → Area Layanan → Kenali Kami →
FAQ → CTA. Gaya "eyebrow kecil di atas H2" dipertahankan, kelas CSS sama persis
(`cegu-*`), sehingga tampilannya menyatu dengan beranda tanpa mengubah CSS.

**Statistik hero mengikuti gaya beranda Anda yang sudah jujur** ("Ratusan",
"Puluhan", "Semua" — bukan persentase karangan). Bila CSV punya data riil
(`harga`, `pengalaman`), angka riil yang dipakai; bila kosong, jatuh ke
"Gratis / Konsultasi Awal" dan "Fleksibel / Jadwal Menyesuaikan" yang tetap benar.

Diuji: nol token bocor, baik saat CSV kaya data maupun kosong. 4 CTA WhatsApp
tersebar (+1 tombol mengambang dari layout).

---

# Catatan versi v2 (generik)


File: `database/data/template-cegu-v2.html` — tempel isinya ke
**Admin → Template** untuk menggantikan template lama.

## Kemampuan baru mesin: blok kondisional

`TokenReplacer` kini mendukung sintaks tambahan:

| Sintaks | Fungsi |
|---|---|
| `{{layanan}}` | Penggantian biasa (seperti sebelumnya) |
| `{{#harga}} … {{/harga}}` | Tampil **hanya bila** token ada & tidak kosong |
| `{{^harga}} … {{/harga}}` | Tampil **justru bila** token kosong (teks cadangan) |
| `{{! catatan }}` | Komentar template — dibuang saat render |

**Kenapa ini penting.** `PageRenderer` memang sudah menghapus token yang tak
terselesaikan, jadi `{{harga}}` tidak bocor mentah ke pengunjung. Namun yang
tersisa adalah **label menggantung** — "Harga mulai:" tanpa isi, atau kotak
statistik kosong. Blok kondisional membuang seluruh blok, bukan cuma tokennya,
sehingga tata letak tetap rapi saat data tidak ada.

Blok boleh bersarang (diproses beberapa lintasan). Sudah diuji: template v2
tidak membocorkan satu token pun, baik saat CSV kaya data maupun kosong.

## Yang berubah di template

### 1. Klaim angka karangan dihapus
Template lama memasang **hardcoded** di setiap halaman:
> "9/10 Tingkat Kepuasan Pelanggan · 100+ Pelanggan Terlayani · 95% Merekomendasikan Kami"

Angka ini tidak berasal dari data mana pun. Bila tidak benar, itu iklan
menyesatkan — dan tampil di jutaan halaman sekaligus. **Diganti** dengan bar
angka yang hanya muncul bila datanya nyata dari CSV (`harga`, `pengalaman`).
Tanpa data, bar-nya tidak tampil sama sekali.

Kalau Anda memang punya angka riil (survei kepuasan, jumlah pelanggan),
masukkan sebagai kolom CSV — nanti tampil otomatis dan jujur.

### 2. Boilerplate identik dikurangi drastis
Template lama punya **10 kotak teks hardcoded** (6 "Nilai Lebih" + 4 "Komitmen
Kualitas") — sekitar 250+ kata yang **sama persis** di setiap halaman. Ini
persis profil yang dinilai Google sebagai konten massal.

Perubahan:
- Bagian "Nilai Lebih" kini memakai `{{usp_list}}` → **didorong variasi konten**,
  jadi berbeda tiap halaman.
- Kotak statis disusutkan dari 10 → 4 inti, dan teksnya kini **menyisipkan
  `{{layanan}}` / `{{kelurahan}}`** sehingga tetap bervariasi.
- Dua kotak tambahan (garansi, legalitas) hanya muncul bila datanya ada.

### 3. Data lokal dinaikkan ke posisi tinggi
`{{fakta_lokal}}` dulu terkubur di bagian "Tentang". Sekarang jadi **section
tersendiri tepat setelah hero** — inilah pembeda utama antar halaman, jadi
layak tampil di atas.

### 4. Bagian baru: "Cara Memesan" (3 langkah)
Menaikkan konversi dan memperjelas alur bagi pengunjung.

### 5. CTA diperbanyak & tersebar
Dari 2 CTA (hero + penutup) menjadi **5 titik**: hero, setelah data lokal,
setelah cara pesan, dan penutup — plus tombol WA mengambang yang sudah ada di
layout. Semua otomatis terlacak oleh pelacakan lead.

### 6. Aksesibilitas & semantik
- Setiap `<section>` punya `aria-labelledby` yang menunjuk ke `<h2>`-nya.
- Ikon SVG diberi `aria-hidden="true"` (dulu terbaca screen reader sebagai sampah).
- Hierarki heading dirapikan: satu `<h1>`, sisanya `<h2>` → `<h3>`.

## Kolom CSV yang kini dimanfaatkan

Isi kolom ini di CSV agar halaman jadi kaya & unik (semua **opsional** —
bagian terkait otomatis tersembunyi bila kosong):

`harga`, `pengalaman`, `garansi`, `legalitas`, `jadwal`, `jam_operasional`,
`landmark`, `stok`, `pengiriman`, `jumlah_tutor`, `sekolah`, `luas_tanah`,
`luas_bangunan`, `kamar`, `komposisi`, `izin_bpom`, `isi`

> Minimal 2 kolom terisi per baris = halaman lolos ambang "tidak tipis"
> (`CEGU_THIN_MIN_FACTS`), sehingga canonical-nya tidak dialihkan ke hub.

## Cara memasang
1. Buka `database/data/template-cegu-v2.html`, salin seluruh isinya.
2. Admin → **Template** → tempel → Simpan.
3. Buka satu salespage, cek tampilannya. Bila ada `{{token}}` mentah terlihat,
   berarti ada salah ketik — laporkan.

Template lama tidak dihapus; Anda bisa kembali kapan saja.

## File
```
BARU     database/data/template-cegu-v2.html
DIUBAH   app/Services/TokenReplacer.php   (blok kondisional + komentar)
```
Perubahan `TokenReplacer` **backward-compatible** — template lama tetap berjalan.
