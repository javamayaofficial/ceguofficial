# Internal Linking & Keseragaman Tampilan

## Tampilan — sudah seragam
Beranda, halaman kategori (hub), dan salespage semuanya `@extends('layouts.site')`
dan memakai fingerprint CSS yang sama (middleware `ApplyThemeFingerprint`).
Artinya nav, footer, warna, tipografi, dan seluruh "chrome" **identik** di semua
halaman — tidak ada yang perlu diubah agar tampilannya sama.

## Struktur internal link (mesh) — sudah saling menyambung
```
Beranda
  → Hub Layanan   /{layanan}
      → Hub Kota      /{layanan}/{kota}
          → Hub Kecamatan  /{layanan}/{kota}/{kecamatan}
              → Salespage      /{layanan}/{kota}/{kecamatan}/{kelurahan}
```
- **Salespage** menampilkan `{{breadcrumb}}` (naik ke semua hub di atasnya) +
  `{{internal_links}}` = "Layanan terkait" (layanan lain di kelurahan yang sama)
  dan "Lokasi terkait" (layanan sama di kelurahan lain sekota).
- **Hub/kategori** menampilkan breadcrumb + grid tautan ke level di bawahnya.
- **Beranda** menaut ke hub layanan & kota.

## Yang baru ditambahkan: footer hub global (di SEMUA halaman)
Sebelumnya footer hanya menaut ke anchor beranda. Sekarang footer (yang tampil di
setiap halaman, termasuk salespage terdalam) menambah dua kolom:
- **Layanan** → hub tiap layanan `/{layanan}`
- **Kota** → hub tiap kota `/{layanan-pertama}/{kota}`

Efeknya: dari halaman mana pun, crawler & pengunjung bisa mencapai semua hub
utama dalam 1 klik, dan sinyal (link equity) dari jutaan salespage terdistribusi
ke hub. Daftar diambil otomatis dari layanan/kota yang punya halaman published,
**di-cache** (`CEGU_CATEGORY_CACHE_TTL`) agar tidak query per-request.

File diubah: `resources/views/layouts/site.blade.php`.

> Tidak perlu konfigurasi. Muncul otomatis begitu ada layanan & kota published.
> Bila ingin footer tetap ramping, batas 8 layanan / 12 kota bisa disetel di
> blok `$__hub` pada layout.
