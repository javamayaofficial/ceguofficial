# 🌐 Panduan Banyak Domain — Satu Mesin, Banyak Website Mandiri

Model yang Anda pakai: **source code yang sama dipasang terpisah di banyak
domain**. Bukan multi-tenant — tiap domain punya database, konten, pengaturan,
dan admin sendiri, tidak saling terhubung. Dokumen ini mengatur tiga hal:
arsitektur server, cara kerja hariannya, dan (paling penting) cara agar
jaringan situs Anda tidak dihukum Google.

---

## A. Arsitektur di Server

```
/opt/cegu-engine/          ← SOURCE MASTER (satu-satunya yang di-update)
/var/www/hebalku.com/       ← instalasi mandiri #1 (herbal)
/var/www/sedotwc-bogor.com/ ← instalasi mandiri #2 (jasa)
/var/www/kavling-cianjur.com/ ← instalasi mandiri #3 (properti)
```

Tiap situs: DB MySQL sendiri, `.env` sendiri, nginx vhost + FastCGI cache
sendiri, 2 worker queue sendiri (supervisor). Redis boleh **satu untuk semua**
karena tiap situs memakai `APP_NAME` unik — Laravel otomatis menjadikannya
prefix cache & queue, sehingga tidak ada tabrakan antar situs. **Inilah alasan
`APP_NAME` wajib berbeda per situs** (skrip pemasang sudah menanganinya; jangan
pernah menyeragamkannya secara manual).

### Memasang situs baru (±3 menit per domain)

```bash
# Sekali saja: taruh source master
sudo mkdir -p /opt/cegu-engine && sudo rsync -a /path/cegu-main/ /opt/cegu-engine/

# Setiap kali ada domain baru:
sudo bash /opt/cegu-engine/deploy/pasang-situs-baru.sh hebalku.com "HerbalKu"
```

Skrip membuat semuanya: direktori, DB + user + password acak, `.env`,
composer, migrate + seed, nginx vhost dengan FastCGI cache khusus situs itu,
2 worker supervisor, dan SSL Let's Encrypt. Setelah selesai, operator tinggal
login `/admin`, muat Paket Konten sesuai niche, isi brand + tagline + WA.

### Menyebarkan update mesin ke semua situs

Saat mesin diperbaiki/ditambah fitur, update source master lalu:

```bash
# Uji dulu di SATU situs:
sudo bash /opt/cegu-engine/deploy/update-semua-situs.sh hebalku.com
# Aman? Sebarkan ke semua:
sudo bash /opt/cegu-engine/deploy/update-semua-situs.sh
```

Skrip menyalin KODE saja — `.env`, database, storage, dan konten tiap situs
tidak tersentuh — lalu menjalankan migrate, membangun ulang cache, dan
me-restart worker per situs.

### Kapasitas: berapa situs per VPS?

Patokan kasar (situs fase awal, <100 ribu halaman published, trafik crawler
normal): **4 GB RAM ≈ 3–5 situs; 8 GB ≈ 6–10 situs**. Pemakan resource
terbesar adalah MySQL (data halaman) dan worker. Pantau dengan `htop` +
`df -h`; pindahkan situs yang mulai besar (>500 ribu halaman published) ke
VPS sendiri. Backup wajib per situs: `mysqldump` tiap DB + `.env`, otomatis
tiap malam (cron).

---

## A2. Repository GitHub: SATU repo untuk semua domain

**Jangan** membuat satu repo per domain. Pola yang benar:

- **Satu repo private** = mesin ini. Kode identik untuk semua situs.
- Perbedaan tiap situs hidup di `.env` + database (brand, konten, pengaturan)
  — dua hal itu memang tidak pernah masuk repo (`.gitignore` sudah mengaturnya).
- Tiap user FastPanel: `git clone` repo yang sama ke direktori situsnya.
- Update mesin = push sekali ke repo → di tiap situs: `git pull && composer
  install --no-dev && php artisan migrate --force && php artisan config:cache
  && php artisan queue:restart` (atau pakai `deploy/update-semua-situs.sh`).
- Pakai **tag rilis** (`v1.0`, `v1.1`, ...): kalau update bermasalah di satu
  situs, rollback = `git checkout v1.0` di situs itu saja.

Satu repo per domain terdengar rapi tapi berujung bencana pemeliharaan:
satu bug = push perbaikan berulang kali, dan repo-repo itu pasti tidak
sinkron dalam sebulan.

---

## B. ⚠️ Bagian Terpenting: Anti-Footprint (agar tidak dicap spam network)

Banyak situs programmatic milik satu orang adalah pola yang Google kenali.
Kalau situs-situs Anda terlihat "kembar", sekali satu terdeteksi, **semuanya
bisa ikut tenggelam**. Aturan mainnya:

**1. Satu niche per domain, dan seriusi tiap domain.**
`hebalku.com` hanya herbal, `sedotwc-bogor.com` hanya jasa itu. Domain
"gado-gado" membingungkan Google soal identitas situs — dan lima situs yang
digarap serius selalu mengalahkan lima puluh situs asal jadi.

**2a. Sidik Jari Tema otomatis (fitur bawaan mesin — aktif sendiri).**
Setiap instalasi men-generate **Kode Tema** unik saat pemasangan (tersimpan di
Pengaturan). Dari kode itu mesin otomatis menurunkan: nama class CSS yang
berbeda (situs A memakai `qx7-hero`, situs B `mv4z-hero` — nol jejak seragam),
palet warna (6 pilihan), tingkat kebulatan sudut & tombol, font, dan salah
satu dari 3 susunan template. Total 216 kombinasi visual × 3 struktur —
dua domain mana pun hampir pasti berbeda tampang dan markup **tanpa Anda
melakukan apa pun**. Tidak suka hasil undiannya? Ganti Kode Tema di
Pengaturan (mis. dari `qx7` ke `pn5`) — tampilan langsung terkocok ulang.
Panel admin dan sitemap tidak tersentuh fitur ini.

**2b. Tiap situs tetap WAJIB terasa seperti bisnis berbeda.**
Sidik jari otomatis menghapus keseragaman TEKNIS; keseragaman KONTEN tetap
tanggung jawab Anda:
- Brand, tagline, logo, nomor WA: berbeda per situs.
- Variasi konten: muat paket sebagai titik awal, lalu **tulis ulang dan
  perbanyak** hingga khas situs itu. Dua situs beda niche yang memakai paket
  bawaan mentah-mentah masih aman; dua situs SATU niche dengan kalimat paket
  yang sama persis = duplikat lintas domain, salah satunya akan dikubur.
- Template: mesin mendukung banyak template — ubah warna, susunan section,
  dan gaya heading per situs (menu Template). Struktur HTML yang identik
  100% di puluhan domain adalah sidik jari paling mudah dilacak.

**3. JANGAN saling menautkan antar situs Anda.**
Tidak di footer, tidak di konten, tidak "partner kami". Jaringan situs yang
saling link adalah definisi buku teks dari link scheme/PBN. Biarkan tiap
situs berdiri sendiri.

**4. Pisahkan identitas teknis seperlunya.**
Satu VPS/IP untuk beberapa situs itu wajar (shared hosting juga begitu) —
bukan pelanggaran. Tapi jangan tambah keseragaman lain: pakai Google Search
Console per situs (boleh satu akun), profil bisnis WA per situs, dan bila
skalanya sudah puluhan domain, pertimbangkan menyebar ke 2–3 server/penyedia.

**5. Ritme peluncuran: satu per satu, bukan serentak.**
Meluncurkan 10 domain berisi ratusan ribu halaman di bulan yang sama adalah
sinyal jaringan spam yang sangat kuat. Pola sehat: luncurkan situs #1 →
jalankan pilot sesuai panduan operator → begitu rasio indeksnya sehat, baru
mulai situs #2. Mesin membuat pemasangan cepat; **kesabaran tetap manual**.

**6. Ukuran sukses per situs, bukan total halaman.**
Portofolio yang sehat: tiap domain punya lead WhatsApp masuk. Domain yang
setelah 2–3 bulan tidak menghasilkan apa pun sebaiknya diperbaiki kontennya
atau dipensiunkan — jangan ditambah volumenya.

---

## C. Rutinitas Mengelola Banyak Situs

**Per situs** tetap berlaku penuh `PANDUAN-OPERATOR-AWAM.md` (checklist
dashboard, ritme publish, rapor GSC). Tambahan untuk level portofolio:

- **Mingguan:** satu spreadsheet rekap — baris = domain; kolom = halaman
  published, terindeks (dari GSC), lead WA minggu ini. Domain hijau boleh
  gas; domain merah direm dan diperbaiki, bukan ditambah.
- **Saat update mesin:** uji di satu situs → sebar ke semua → cek cepat
  `/admin` + satu salespage acak per situs.
- **Bulanan:** cek `Manual Actions` di GSC SEMUA situs (5 menit per situs —
  ini alarm kebakaran Anda), cek sisa disk & backup jalan.
- **Keamanan:** password admin berbeda per situs; kredensial DB otomatis
  unik (dibuat skrip); jangan pernah memakai satu password untuk semuanya.

---

## D. Checklist Peluncuran Domain Baru

1. ☐ Jalankan `pasang-situs-baru.sh <domain> "<Brand>"`
2. ☐ Login `/admin` → Pengaturan: brand, tagline, WA, logo/hero khas situs ini
3. ☐ Variasi Konten → Muat Paket sesuai niche → tulis ulang & perbanyak
4. ☐ Template → sesuaikan warna/susunan agar tidak kembar dengan situs lain
5. ☐ Dashboard → checklist onboarding sampai hijau semua
6. ☐ Import CSV pilot (1 kota) → cek sampel → publish → daftar GSC
7. ☐ Masukkan domain ke spreadsheet rekap portofolio
8. ☐ Tunggu rasio indeks sehat sebelum ekspansi ATAU sebelum meluncurkan
     domain berikutnya
