# Skema Multi-Domain & Multi-Server — DAYA AI

Jawaban untuk: *"bagaimana saat menambah domain baru dan server baru?"*

---

## 1. Model arsitekturnya: instalasi terpisah, bukan multi-tenant

Satu domain = **satu instalasi penuh dan mandiri**:

```
/var/www/herbalku.com/     ← kode + .env + storage sendiri
        └── database: daya_herbalku_com

/var/www/propertibogor.com/
        └── database: daya_propertibogor_com
```

Alasannya: seluruh data mesin ini bersifat **global per instalasi** —
Pengaturan (brand, WhatsApp), stok variasi konten, FAQ, template, dan otak MD
tidak punya kolom pemilik. Mengubahnya jadi multi-tenant berarti membongkar
hampir semua tabel dan query. Untuk skala puluhan klien, instalasi terpisah
jauh lebih murah risikonya.

**Konsekuensi yang harus Anda terima:** biaya operasional tumbuh **linear**.
10 klien = 10 database, 10 worker, 10 kali update. Itu wajar sampai ~20–30
situs; di atas itu barulah multi-tenant sungguhan layak dipertimbangkan.

---

## 2. Dua skenario penempatan

### A. Banyak domain dalam SATU VPS (disarankan untuk awal)

```
VPS (4 vCPU / 8 GB)
├── nginx           → vhost per domain
├── php-fpm         → dipakai bersama
├── mysql           → 1 database per situs
├── redis           → dipakai bersama, dipisah lewat APP_NAME
├── supervisor      → 1 grup worker per situs
└── /var/www/<domain>/ × N
```

**Cocok bila:** klien masih sedikit (< 10), trafik per situs belum besar.
**Untung:** hemat biaya, satu kali update menjangkau semua situs.
**Risiko:** satu server bermasalah → semua situs ikut mati.

> **Isolasi Redis itu WAJIB.** `APP_NAME` harus unik tiap situs karena Laravel
> memakainya sebagai prefix cache/queue. Kalau dua situs punya `APP_NAME` sama,
> cache dan antrian mereka **bercampur** — halaman situs A bisa muncul di situs B.
> Skrip pemasangan sudah menanganinya otomatis.

### B. Server terpisah per klien

**Cocok bila:** klien besar/sensitif, trafik tinggi, atau butuh jaminan isolasi.
**Untung:** aman total, satu situs bermasalah tidak menular.
**Risiko:** biaya × N, dan update harus dijalankan di tiap server.

**Rekomendasi saya:** mulai dengan skenario A. Pindahkan klien ke server sendiri
hanya ketika ada alasan nyata (trafik besar, permintaan klien, atau sudah
mengganggu tetangga). Jangan menyiapkan 10 server untuk 3 klien.

---

## 3. Apa yang dibagi, apa yang harus unik

| Item | Dibagi antar situs? | Catatan |
|---|---|---|
| Source code | ✅ dari satu master | `/opt/daya-engine` |
| Dataset wilayah (2,2 MB) | ✅ ikut kode | Duplikasi per situs, ukurannya kecil |
| Otak MD (default) | ✅ ikut kode | Disalin ke `storage/app/brain` saat pasang |
| **Kunci AI** | ✅ boleh sama | Satu akun OpenRouter cukup — biaya per pemakaian, bukan per instalasi |
| **APP_NAME** | ❌ WAJIB unik | Prefix cache/queue Redis |
| **Database** | ❌ WAJIB terpisah | |
| **INDEXNOW_KEY** | ❌ WAJIB unik | Diverifikasi per domain |
| **Verifikasi Search Console** | ❌ per domain | Klaim kepemilikan masing-masing |
| **Nomor WhatsApp** | ❌ per klien | |
| **Kredensial admin** | ❌ per situs | Skrip membuat password acak |

---

## 4. Menambah domain baru (satu perintah)

```bash
# Sekali per VPS: siapkan source master
git clone <repo> /opt/daya-engine

# Tiap klien baru:
sudo AI_API_KEY="sk-or-v1-xxx" \
  bash /opt/daya-engine/deploy/pasang-situs-baru.sh herbalku.com "HerbalKu"
```

Skrip mengerjakan 8 tahap otomatis: salin kode → pasang otak MD → buat database
→ tulis `.env` (termasuk **IndexNow key unik**) → composer+migrate → vhost Nginx
dengan micro-cache → worker Supervisor → SSL + cron.

Di akhir, skrip mencetak kredensial admin dan IndexNow key — **catat di password
manager**.

### Yang tersisa untuk operator (5–10 menit)
1. Login admin → **Pengaturan**: nama brand, tagline, **nomor WhatsApp**.
2. Menu **🚀 Mulai Cepat**: isi kata kunci + wilayah → konten & halaman terisi otomatis.
3. Periksa 5–10 halaman sampel → publish bertahap.
4. Submit `sitemap.xml` ke Search Console + tempel kode verifikasi.

---

## 5. Menyebarkan update ke semua situs

```bash
cd /opt/daya-engine && git pull                    # perbarui master
sudo bash /opt/daya-engine/deploy/update-semua-situs.sh   # sebar ke semua
sudo bash deploy/update-semua-situs.sh herbalku.com       # atau satu situs saja
```

Skrip menyalin **kode saja** — `.env`, database, storage, dan uploads tiap situs
tidak disentuh. File otak MD baru ditambahkan tanpa menimpa yang sudah
disesuaikan operator.

> **Selalu uji di satu situs terkecil dulu** sebelum menjalankan ke semua.
> Kalau ada migrasi database baru, kegagalan di satu situs bisa terulang di
> semua situs sekaligus.

Untuk server terpisah: jalankan perintah yang sama di tiap server. Bila server
sudah banyak, pertimbangkan Ansible atau sekadar skrip SSH berulang.

---

## 6. Perbaikan penting yang menyertai skema ini

**Data demo tidak lagi ikut terpasang.** Sebelumnya `db:seed` menjalankan
`ContentSeeder` + `SamplePagesSeeder`, sehingga tiap instalasi baru mewarisi
konten dan FAQ contoh — persis penyebab masalah *"FAQ demo bercampur konten
baru"* di cegu.co.id.

Sekarang `db:seed` hanya membuat **akun admin**. Data demo dipindah ke seeder
terpisah dan hanya jalan bila diminta:
```bash
php artisan db:seed --class=Database\\Seeders\\DemoSeeder
```

---

## 7. Perkiraan kapasitas (indikatif — ukur sendiri)

Dengan micro-cache Nginx aktif, beban PHP per situs kecil karena crawler
sebagian besar dilayani dari cache.

| VPS | Perkiraan jumlah situs |
|---|---|
| 2 vCPU / 4 GB | 3–5 situs kecil |
| 4 vCPU / 8 GB | 8–12 situs |
| 8 vCPU / 16 GB | 20+ situs |

Yang paling cepat menghabiskan sumber daya bukan trafik, melainkan **worker
antrian saat generate massal**. Hindari menjalankan generate jutaan halaman di
beberapa situs secara bersamaan.

---

## 8. Checklist klien baru

- [ ] Domain diarahkan ke IP server (A record)
- [ ] `pasang-situs-baru.sh` dijalankan
- [ ] Kredensial admin + IndexNow key disimpan
- [ ] Pengaturan: brand, tagline, **nomor WhatsApp asli**
- [ ] Logo/`og_image` (JPG/PNG, URL absolut)
- [ ] Mulai Cepat: kata kunci + wilayah + data lokal riil
- [ ] Testimoni asli (atau kosongkan dulu)
- [ ] Publish bertahap, mulai 5–10 halaman
- [ ] Search Console: verifikasi + submit sitemap
- [ ] Google Analytics (opsional)

---

## File
```
DIUBAH  deploy/pasang-situs-baru.sh      (DAYA, AI key, IndexNow unik, otak MD, PHP 8.4)
DIUBAH  deploy/update-semua-situs.sh     (source /opt/daya-engine, sinkron otak MD)
DIUBAH  database/seeders/DatabaseSeeder.php   (tanpa data demo)
BARU    database/seeders/DemoSeeder.php       (data demo, opsional)
BARU    database/data/brain/*.md              (otak MD ikut repo → tiap instalasi dapat)
```
