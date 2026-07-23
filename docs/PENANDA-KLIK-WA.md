# Penanda Klik WhatsApp — Nomor CS & Konfirmasi Terbuka

Melengkapi panel Lead: sekarang tercatat **nomor CS mana** yang menerima klik
(karena mesin memakai rotator), dan **apakah klik itu benar-benar membuka
WhatsApp**. Isi percakapan tidak dilacak sama sekali.

## 1. Nomor CS mana yang menerima klik

Setting `whatsapp_number` boleh berisi banyak nomor — mesin memilihnya
deterministik per halaman (rotator). Sebelumnya tidak diketahui nomor mana yang
kebagian klik.

Sekarang nomor diambil **langsung dari tautan yang diklik**, jadi selalu cocok
dengan rotator apa pun yang sedang berlaku di halaman itu.

Panel menampilkan tabel: nomor CS · jumlah klik · terbuka · rasio.

**Gunanya:**
- Melihat beban tiap CS (siapa paling banyak menerima).
- Memastikan rotator membagi **merata** — kalau satu nomor jauh mendominasi,
  berarti sebaran halamannya timpang.

## 2. Penanda "sampai masuk ke WhatsApp"

Klik tombol saja belum tentu berujung terbuka — bisa salah pencet, atau
WhatsApp tidak terpasang.

Cara kerja penandanya: saat tombol diklik, browser mengirim token acak. Bila
dalam **10 detik** halaman berpindah ke latar belakang (pertanda aplikasi/tab
WhatsApp terbuka), browser mengirim konfirmasi dengan token yang sama, dan
kolom `opened_at` terisi.

**Ini PROKSI, bukan kepastian:**
- Sebagian perangkat/browser tidak mengirim sinyal `visibilitychange` →
  angka "terbuka" bisa **lebih rendah** dari kenyataan.
- Terbuka ≠ pesan terkirim. Orang tetap bisa batal di aplikasi WhatsApp.

Jadi bacalah: **klik** = minat, **terbuka** = benar-benar sampai ke WhatsApp,
**chat terkirim** = hanya CS yang tahu.

## Cara membaca rasio

| Rasio terbuka | Kemungkinan artinya |
|---|---|
| 70–100% | Sehat — tombol berfungsi baik |
| 30–60% | Wajar (banyak perangkat tidak melapor) |
| Mendekati 0% padahal klik banyak | Periksa: nomor WA salah? tautan rusak? |

## File
```
BARU    database/migrations/..._add_wa_number_and_opened_to_lead_clicks.php
DIUBAH  app/Models/LeadClick.php
DIUBAH  app/Http/Controllers/LeadTrackController.php     (terima nomor + konfirmasi)
DIUBAH  app/Http/Controllers/Admin/LeadController.php    (statistik per nomor)
DIUBAH  resources/views/admin/leads/index.blade.php      (tabel sebaran CS)
DIUBAH  resources/views/layouts/site.blade.php           (pelacak JS)
```

Setelah pasang:
```bash
php artisan migrate
php artisan view:clear
```
Data nomor CS mulai terkumpul dari klik BARU — klik lama tidak punya datanya.
