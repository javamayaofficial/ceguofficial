# Rebranding: CEGU → DAYA AI

Mesin ini kini bernama **DAYA AI** — produk pSEO yang dipakai untuk banyak
bisnis. Nama klien tetap diatur terpisah lewat **Pengaturan → Nama Brand**.

> **Dua nama yang berbeda, jangan tertukar:**
> - **DAYA AI** = nama MESIN/produk (tampil di panel admin)
> - **Nama Brand** = nama BISNIS klien (tampil di halaman publik, mis. "Central Guru")
>
> Satu instalasi = satu klien. Nama brand berbeda tiap instalasi, nama mesin sama.

## Yang diganti

| Bagian | Sebelum | Sesudah |
|---|---|---|
| Panel admin | ⚙️ CEGU Engine | ⚡ DAYA AI |
| Judul tab admin | — CEGU pSEO Engine | — DAYA AI |
| File config | `config/cegu.php` | `config/daya.php` |
| Pemanggilan config | `config('cegu.*')` | `config('daya.*')` (8 referensi) |
| Variabel .env | `CEGU_*` | `DAYA_*` (6 variabel) |
| Kunci cache | `cegu:*` | `daya:*` (7 file) |

Nama mesin kini **konfigurabel** lewat `DAYA_ENGINE_NAME` di `.env` — berguna
bila suatu saat Anda ingin white-label untuk klien tertentu.

## Kompatibilitas .env (penting)

`config/daya.php` masih membaca variabel lama sebagai cadangan:

```php
'page_cache_ttl' => (int) env('DAYA_PAGE_CACHE_TTL', env('CEGU_PAGE_CACHE_TTL', 900)),
```

Artinya: **server yang `.env`-nya belum diperbarui tetap berjalan normal.**
Anda bisa mengganti nama variabelnya kapan saja tanpa terburu-buru. Setelah
mengubah `.env`, jalankan `php artisan config:clear`.

---

## ⚠️ Yang SENGAJA TIDAK diganti: prefiks CSS `cegu-`

Ada **246 kemunculan** kelas CSS `cegu-btn`, `cegu-section`, `cegu-tile`, dst.
Semuanya saya biarkan. Ini keputusan sadar, bukan kelalaian.

**Alasan 1 — risiko merusak situs live.** Template salespage Anda tersimpan di
**database**, bukan di kode. Kalau prefiks CSS diganti di kode tetapi template di
DB masih memakai `cegu-*`, seluruh halaman kehilangan styling. Untuk mengganti
dengan aman, semua template di DB harus ikut diperbarui — pekerjaan berisiko
tanpa jaring pengaman.

**Alasan 2 — tidak terlihat pengunjung, dan sudah bisa diubah.** `cegu-` hanyalah
token internal. Middleware `ApplyThemeFingerprint` sudah menuliskannya ulang di
output sesuai Setting `theme_prefix`. Jadi kalau Anda ingin kelas CSS di HTML
publik berbunyi `daya-btn` (atau apa pun), cukup ubah setelan itu di panel —
**tanpa menyentuh satu baris kode pun.**

Singkatnya: mengganti 246 kemunculan itu berisiko tinggi, bermanfaat nol, dan
tujuannya sudah tercapai lewat jalur yang aman.

## Folder `deploy/` juga tidak diganti

Berkas Nginx/Supervisor masih memakai nama `cegu-worker`, zona cache `CEGU`, dan
path contoh `/var/www/cegu`. Itu **nama di server Anda yang sudah berjalan** —
menggantinya berarti harus mengonfigurasi ulang Supervisor dan Nginx tanpa alasan
kuat. Untuk instalasi klien BARU, silakan pakai nama apa pun; berkas itu memang
template yang disesuaikan.

---

## File
```
BARU     config/daya.php                          (menggantikan config/cegu.php)
DIHAPUS  config/cegu.php
DIUBAH   8 file (config('cegu.*') → config('daya.*'))
DIUBAH   7 file (kunci cache cegu: → daya:)
DIUBAH   resources/views/admin/layout.blade.php   (branding dari config)
DIUBAH   .env.example                             (DAYA_*, APP_NAME, DAYA_ENGINE_NAME)
```

## Setelah pasang
```bash
php artisan config:clear
php artisan cache:clear      # kunci cache berubah prefiks
php artisan view:clear
```

Verifikasi: buka panel admin — sidebar harus menampilkan **⚡ DAYA AI**.
Halaman publik tidak berubah sama sekali (memang tidak seharusnya).
