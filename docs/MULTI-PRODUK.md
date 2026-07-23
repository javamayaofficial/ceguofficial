# Mesin Multi-Produk — Warna, Logo, Gambar, dan Alt Text

Struktur salespage sengaja **seragam untuk semua produk**. Yang membedakan tiap
instalasi hanya tiga hal, dan semuanya diatur dari panel admin.

## 1. Warna — sekarang bisa diatur (BARU)

Sebelumnya palet dikunci ke satu bisnis (teal Central Guru) di dalam kode.
Untuk mesin lintas produk itu tidak masuk akal.

Sekarang ada kartu **🎨 Warna Situs** di Pengaturan dengan 6 pilihan:

| Kolom | Dipakai untuk |
|---|---|
| Warna utama | Hero, tombol, judul |
| Warna utama gelap | Gradient & hover |
| Warna aksen | Angka, sorotan |
| Warna tombol sekunder | CTA alternatif |
| Latar halaman | Background |
| Warna teks | Teks utama |

Tiap kolom punya color picker + kolom HEX. Kosongkan untuk memakai bawaan.

**Keamanan:** hanya kode HEX yang sah diterima (`#abc` atau `#aabbcc`). Nilai
lain diabaikan, sehingga kolom warna tidak bisa dipakai menyuntikkan CSS.

## 2. Logo & gambar section — sudah ada

Diatur di Pengaturan (bukan di template):

| Setting | Muncul di |
|---|---|
| `organization_logo` / `logo_image` | Navigasi & footer |
| `hero_image` | Gambar hero |
| `image_keunggulan` | Section keunggulan |
| `image_solusi` | Section solusi |
| `image_tentang` | Section tentang |
| `og_image` | Preview media sosial |

Cukup unggah/tempel URL sekali — berlaku di semua halaman situs itu.

## 3. Alt text berbeda tiap halaman — sudah berjalan

Ini yang Anda tanyakan: walau **gambarnya sama**, alt text-nya berbeda per
halaman, karena disusun dari data halaman itu sendiri.

| Gambar | Pola alt |
|---|---|
| Hero | Teks hero halaman itu (diambil dari pool variasi → beda tiap halaman) |
| Keunggulan | `Keunggulan {layanan} di {kelurahan}, {kota}` |
| Solusi | `Solusi {layanan} di {kelurahan}` |
| Tentang | `Tentang {layanan} di {kecamatan} - {brand}` |

Jadi satu foto yang sama menghasilkan ribuan alt text unik — sesuai lokasi dan
layanan tiap halaman. Ini menghindari sinyal duplikat pada gambar.

> Perbaikan: alt "Tentang" kini ikut menyebut kecamatan (sebelumnya sama di
> semua halaman untuk layanan yang sama).

## 4. Perbaikan sisa jejak satu-bisnis

Dua nilai bawaan masih terhardcode ke klien pertama dan sudah dihapus:

- `'Central Guru'` sebagai fallback alt gambar tentang
- `'CEGU'` sebagai fallback token `{{brand}}`

Sekarang keduanya memakai Setting `brand_name` milik instalasi masing-masing.
Bila belum diisi, dibiarkan kosong — bukan memakai nama bisnis lain.

---

## Template tetap umum

12 template kerangka copywriting yang sudah dibuat memakai istilah netral
(`{{layanan}}`, "pelanggan", "pemesanan") sehingga cocok untuk jasa maupun
produk. Yang spesifik per bisnis masuk lewat:
- Token lokasi & layanan (otomatis)
- Pool variasi konten (diisi AI/manual per instalasi)
- Kolom data lokal CSV (harga, jadwal, dll.)

## File
```
BARU    app/Support/BrandColors.php                      (palet + validasi HEX)
DIUBAH  app/Support/SalespageStyles.php                  (terapkan warna admin)
DIUBAH  app/Services/PageRenderer.php                    (hapus fallback hardcoded, alt lebih variatif)
DIUBAH  app/Http/Controllers/Admin/SettingController.php (kunci warna + validasi)
DIUBAH  resources/views/admin/settings/edit.blade.php    (kartu Warna Situs)
```

Setelah pasang:
```bash
php artisan config:clear && php artisan cache:clear && php artisan view:clear
```
Lalu buka salespage dan tekan **Ctrl+Shift+R** (cache browser).
