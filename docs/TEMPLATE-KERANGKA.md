# 12 Template Salespage — Kerangka Copywriting Berbeda

Template **cangkang** (shell): strukturnya berbeda-beda, isinya diambil dari pool
variasi konten sehingga tiap halaman tetap unik.

## Daftar

| File | Kerangka | Cocok untuk |
|---|---|---|
| `01-aida.html` | Attention → Interest → Desire → Action | Produk umum, UMKM, landing sederhana |
| `02-pas.html` | Problem → Agitate → Solution | Jasa yang menyelesaikan masalah, konsultan |
| `03-bab.html` | Before → After → Bridge | Produk transformasi, kursus, coaching |
| `04-4p.html` | Promise → Picture → Proof → Push | Produk digital, webinar, lead magnet |
| `05-quest.html` | Qualify → Understand → Educate → Stimulate → Transition | Audiens yang perlu edukasi |
| `06-tofu.html` | Top of Funnel | Audiens baru, awareness |
| `07-mofu.html` | Middle of Funnel | Sudah kenal, tahap pertimbangan |
| `08-bofu.html` | Bottom of Funnel | Siap membeli, closing |
| `09-vsl.html` | Video Sales Letter (alur bertahap) | Harga tinggi, high ticket |
| `10-advertorial.html` | Soft selling ala artikel | Native marketing |
| `11-longform.html` | Long Form Salesletter | Produk kompleks, penjualan mendalam |
| `12-fsp.html` | Fakta → Story → Penawaran | Pasar Indonesia, WhatsApp, story selling |

## Jaminan kompatibilitas (sudah diverifikasi otomatis)

Semua template lolos pemeriksaan berikut:

| Pemeriksaan | Hasil |
|---|---|
| Hanya memakai token yang **didukung mesin** | ✅ 12/12 |
| Memuat **semua daftar variasi** (`usp_list`, `pain_point_list`, `solusi_list`, `testimoni_list`) | ✅ 12/12 |
| Memuat token SEO wajib (`breadcrumb`, `internal_links`, `faq`, `cta`) | ✅ 12/12 |
| Memuat token lokasi (`layanan`, `kelurahan`, `kecamatan`, `kota`, `brand`) | ✅ 12/12 |
| Tepat **satu `<h1>`** per halaman | ✅ 12/12 |
| Tag HTML seimbang (`section`, `header`, `div`) | ✅ 12/12 |
| **Nol token bocor** saat dirender | ✅ 12/12 |
| Struktur antar template benar-benar **berbeda** | ✅ 12/12 unik |

**Kenapa ini penting:** kalau sebuah template tidak memakai `pain_point_list`,
maka seluruh variasi pain point di pool tidak akan pernah tampil di halaman itu —
sebaran variasi jadi lemah. Generator awal sempat melewatkan ini pada 6 template
dan sudah diperbaiki.

## Standar kode yang diikuti

- **Hanya sintaks `{{token}}` standar.** Tidak ada `{{#...}}` / `{{! ... }}`,
  sehingga aman dijalankan pada versi `TokenReplacer` mana pun.
- **Kelas CSS `cegu-*` dipertahankan** — ini token internal yang ditulis ulang
  middleware sesuai setelan `theme_prefix`. Mengubahnya akan merusak styling.
- Setiap `<section>` punya `aria-labelledby`, ikon diberi `aria-hidden`.
- 3 tombol WhatsApp tersebar per halaman (+1 tombol mengambang dari layout).

## Cara pasang

1. Admin → **Template** → **Tambah Template**.
2. Salin isi salah satu file `.html` → tempel → beri nama (mis. "AIDA").
3. Ulangi untuk kerangka lain yang ingin Anda pakai.
4. Aktifkan salah satu (hanya satu template aktif dalam satu waktu).

> **Catatan penting:** mesin memakai **satu template aktif** untuk semua
> salespage. Jadi 12 template ini adalah **pilihan gaya**, bukan rotasi otomatis
> per halaman. Untuk memakai kerangka berbeda per niche, gunakan instalasi
> terpisah — atau ganti template aktif saat fokus kampanye berubah.

## Saran pemilihan

- Baru mulai, belum tahu audiens → **AIDA** atau **PAS**
- Jasa lokal Indonesia dengan closing WhatsApp → **FSP** atau **BOFU**
- Ingin ranking untuk kata kunci informasional → **TOFU**
- Produk mahal butuh penjelasan panjang → **VSL** atau **Long Form**

Uji satu template di beberapa halaman, lihat data klik WhatsApp di panel Lead,
lalu pertahankan yang menghasilkan lead terbanyak. Itu keputusan berbasis bukti,
bukan selera.
