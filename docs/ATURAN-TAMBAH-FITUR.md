# Isolasi Antar Domain — Verifikasi & Aturan Menambah Fitur

Jawaban untuk: *"kalau nambah fitur untuk niche kesehatan, apakah bocor ke niche properti?"*

---

## Hasil verifikasi: arsitekturnya AMAN

Tiga hal sudah diperiksa langsung di kode:

**1. Update hanya menyalin KODE, tidak menyentuh data situs.**
`update-semua-situs.sh` mengecualikan: `.env`, `storage/`, `public/uploads/`,
`vendor/`, `bootstrap/cache/`, dan database SQLite. Jadi pengaturan, konten,
gambar, dan database tiap situs tidak pernah tertimpa.

**2. Tidak ada migrasi yang menyisipkan DATA.**
Diperiksa seluruh folder `database/migrations/` — semuanya murni mengubah
struktur tabel, nol `insert`. Ini penting: migrasi berjalan di SEMUA situs saat
update, jadi kalau ada satu saja migrasi yang menyisipkan konten niche tertentu,
konten itu akan muncul di semua klien.

**3. Fitur khas niche dikunci oleh DATA, bukan oleh kode.**
Contoh nyata di `SummaryGenerator`:
```php
if (! empty($extra['jumlah_tutor'])) {
    $facts[] = 'Saat ini ' . $extra['jumlah_tutor'] . ' tenaga pengajar aktif…';
}
```
Kalimat ini hanya muncul bila CSV situs itu punya kolom `jumlah_tutor`. Situs
properti tidak punya kolom tersebut → kalimat tidak pernah tampil. **Inilah pola
yang benar**: fitur tersedia untuk semua, aktif hanya bila datanya ada.

---

## Yang SAYA TEMUKAN bocor (sudah diperbaiki)

Arsitekturnya aman, tetapi ada kebocoran di level **teks antarmuka**:

| Lokasi | Sebelum | Masalah |
|---|---|---|
| `ContentHealthService` | *"Kolom opsional (harga, **jumlah_tutor**, landmark, **sekolah**)…"* | Klien properti membaca saran soal tutor & sekolah |
| `SiteAuditService` | *"Tambahkan kolom seperti harga, **jumlah_tutor**…"* | Asisten SEO menyarankan kolom yang tidak relevan |

Keduanya kini memakai kalimat netral: *"kolom data lokal sesuai bisnis Anda
(mis. harga, jadwal, landmark)"*.

Ini contoh bagus bahwa risikonya **bukan pada arsitektur, melainkan pada
kedisiplinan menulis fitur**.

---

## ATURAN menambah fitur baru (agar tidak bocor antar niche)

### ✅ AMAN — silakan
| Pola | Kenapa aman |
|---|---|
| Menambah **token** baru (`{{izin_bpom}}`) | Kosong bila kolom CSV tidak ada |
| Menambah **kolom Pengaturan** baru | Kosong bila tidak diisi operator |
| Menambah **preset** (paket halaman, template) | Opt-in — operator yang memilih |
| Menambah **menu/halaman admin** | Netral, dipakai semua niche |
| Migrasi **struktur** (tambah kolom/tabel) | Tidak membawa isi |
| Logika ber-**guard** `if (! empty($extra['x']))` | Mati sendiri bila data tak ada |

### ❌ BERBAHAYA — hindari
| Pola | Akibat |
|---|---|
| Migrasi yang **INSERT data** | Konten niche masuk ke semua klien |
| Teks niche **hardcoded** di view/service bersama | Klien lain membaca istilah asing |
| **Default Pengaturan** berisi nilai niche | Semua situs baru mewarisinya |
| **Seeder** dipanggil otomatis saat update | Sama seperti migrasi berisi data |
| Menaruh konten di `database/data/` lalu di-**auto-import** | Menyebar ke semua situs |

### Uji cepat sebelum menambah fitur
> *"Kalau fitur ini aktif di situs properti yang operatornya tidak melakukan
> apa-apa, apakah ada yang berubah di halaman publiknya?"*
>
> Bila **YA** → fitur itu terlalu memaksa, beri guard atau jadikan opt-in.
> Bila **TIDAK** → aman.

---

## Cara mengurangi risiko saat update

1. **Uji di satu situs terkecil dulu**, baru sebar:
   ```bash
   sudo bash update-semua-situs.sh situs-uji.com     # satu situs
   sudo bash update-semua-situs.sh                    # semua
   ```
2. **Periksa migrasi baru** sebelum menyebar — pastikan tidak ada `insert`.
3. **Cadangkan database** sebelum update besar:
   ```bash
   mysqldump daya_namasitus > backup-$(date +%F).sql
   ```

## Batas yang tetap ada

Karena semua situs berbagi satu basis kode, **fitur tidak bisa dinonaktifkan
per-situs lewat kode** — hanya lewat data/pengaturan. Bila suatu saat ada klien
yang butuh perilaku benar-benar berbeda (bukan sekadar isi berbeda), pilihannya:
buat cabang kode terpisah untuk klien itu, atau tambahkan saklar di Pengaturan.

## File yang diperbaiki
```
DIUBAH  app/Services/ContentHealthService.php   (petunjuk kolom dinetralkan)
DIUBAH  app/Services/Ai/SiteAuditService.php    (saran asisten dinetralkan)
DIUBAH  app/Services/SummaryGenerator.php       (kalimat 'sekolah' dinetralkan)
```
