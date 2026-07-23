# Soal Kuota Inspeksi URL — Koreksi & Solusi

## Menambah service account TIDAK menambah kuota

Kuota URL Inspection API (**2.000/hari**) dihitung **per properti/situs**, bukan
per user atau per service account. Menambahkan 10 service account sebagai user
di Search Console tetap berbagi kolam 2.000/hari yang sama untuk properti itu.

Yang bisa dilipatgandakan hanya kuota **per Google Cloud project** (10.000/hari),
tetapi batas per-properti tetap mengunci di 2.000. Jadi usaha menambah akun
tidak membuahkan hasil untuk satu domain.

> Untuk arsitektur multi-klien Anda hal ini justru sudah beres: tiap domain =
> properti sendiri = jatah 2.000/hari sendiri. 10 klien = 20.000/hari total,
> tanpa perlu trik apa pun.

## Anda belum menyentuh batas itu

| Jumlah halaman | Waktu inspeksi penuh |
|---|---|
| 63 (Depok) | 1 hari |
| 1.439 (Jabodetabek) | **1 hari** |
| 10.000 | 5 hari |
| 100.000 | 50 hari |

Skala Anda sekarang jauh di bawah batas. Kuota bukan kendala yang sedang Anda hadapi.

---

## Solusi sebenarnya untuk skala besar (sudah dipasang)

### 1. Estimasi terindeks dari data pencarian — TANPA kuota per-URL

Halaman yang punya **impresi** di Search Analytics pasti sudah terindeks — tidak
mungkin muncul di hasil pencarian bila tidak ada di indeks.

Search Analytics API mengembalikan **25.000 baris sekali panggil** dan bisa
dipaginasi. Jadi memantau 100.000 halaman pun tidak menyentuh kuota inspeksi
sama sekali.

Tampil di panel sebagai **"Terindeks menurut data pencarian"**.

> Jujur soal batasnya: angka ini adalah **batas bawah**. Halaman yang sudah
> terindeks tetapi belum pernah muncul di pencarian tidak terhitung. Jadi
> bacalah sebagai "minimal sekian halaman sudah terindeks".

### 2. Mode sampel acak untuk URL Inspection

Untuk situs sangat besar, memeriksa semua halaman itu boros dan tidak perlu.
Centang **"Mode sampel acak"** → sistem memeriksa beberapa ratus halaman acak.
Dari situ Anda tahu **persentase** terindeks untuk keseluruhan situs.

400 halaman sampel sudah cukup mewakili, berapa pun total halaman Anda — dan
hanya memakai 20% kuota harian.

---

## Kapan pakai yang mana

| Kebutuhan | Pakai |
|---|---|
| "Berapa halaman saya yang terindeks?" (skala besar) | Estimasi data pencarian |
| "Berapa persen situs saya terindeks?" | Mode sampel acak |
| "Kenapa halaman X ini tidak terindeks?" | Inspeksi normal / tombol Cek ulang |
| "Saya mau halaman X cepat masuk indeks" | Tombol *Minta Index di GSC ↗* (manual, ~10-12/hari) |

## File
```
DIUBAH  app/Services/SearchConsole/SearchConsoleService.php  (indexedByAnalytics)
DIUBAH  app/Jobs/InspectPagesJob.php                         (mode sampling)
DIUBAH  app/Http/Controllers/Admin/IndexingController.php
DIUBAH  resources/views/admin/indexing/index.blade.php
```
