# Gambar di Halaman Statis — Termasuk Memakai Ulang Gambar Situs

Halaman statis (Tentang, Layanan, Kontak) kini punya **tiga sumber gambar**,
sehingga Anda tidak perlu mengunggah hal yang sama berulang kali.

---

## Sumber 1 — Gambar situs yang sudah ada (tanpa unggah ulang)

Gambar yang sudah diisi di **Pengaturan** (dan dipakai beranda/salespage) bisa
langsung dipanggil di halaman statis:

| Token | Mengambil dari |
|---|---|
| `{{situs_hero}}` | Gambar hero |
| `{{situs_keunggulan}}` | Gambar Keunggulan |
| `{{situs_solusi}}` | Gambar Solusi |
| `{{situs_proses}}` | Gambar Proses |
| `{{situs_tentang}}` | Gambar Tentang |
| `{{situs_galeri}}` | Galeri 1–6 (grid) |

Contoh isi halaman "Tentang Kami":
```html
<h2>Siapa Kami</h2>
<p>{{brand}} melayani sejak {{year}}.</p>
{{situs_tentang}}
<h2>Dokumentasi</h2>
{{situs_galeri}}
```

## Sumber 2 — Hero otomatis sesuai topik

Bila halaman **belum punya gambar hero sendiri**, sistem memilihkan gambar situs
yang topiknya cocok dengan slug:

| Slug mengandung | Memakai gambar |
|---|---|
| `tentang`, `profil`, `about`, `kenali` | Gambar Tentang |
| `layanan`, `jasa`, `produk`, `program` | Gambar Keunggulan |
| `proses`, `cara`, `alur`, `prosedur` | Gambar Proses |
| `solusi`, `manfaat` | Gambar Solusi |
| lainnya | Gambar hero situs (cadangan) |

Contoh nyata:
```
/tentang-kami       → gambar Tentang
/layanan            → gambar Keunggulan
/cara-pesan         → gambar Proses
/kontak-kami        → gambar hero (cadangan)
```

Jadi begitu Anda membuat halaman "Layanan", gambarnya **langsung muncul**
mengikuti yang sudah ada di beranda — tanpa mengisi apa pun.

## Sumber 3 — Gambar khusus halaman itu

Bila halaman butuh gambar sendiri, isi **hero** dan/atau **4 gambar isi**
(masing-masing dengan alt opsional). Sisipkan dengan `{{gambar1}}`…`{{gambar4}}`
atau `{{galeri}}`.

Gambar khusus **selalu menang** atas pencocokan otomatis.

---

## Urutan prioritas hero

```
1. hero_image halaman ini (bila diisi)
2. gambar situs yang cocok dengan topik slug
3. gambar hero situs
4. tidak ada gambar → section hero dilewati
```

## Alt text

- Gambar isi: alt sendiri, atau otomatis `{judul halaman} - gambar {N}`
- Gambar situs: alt memakai **judul halaman**, sehingga satu gambar yang dipakai
  di beberapa halaman tetap punya alt berbeda
- Galeri situs: `{judul halaman} - foto {N}`

## Galeri otomatis

Bila konten tidak memuat token gambar apa pun (`{{gambar…}}`, `{{galeri}}`,
`{{situs_…}}`), gambar isi halaman tampil otomatis sebagai galeri di bawah —
supaya gambar yang sudah diisi tidak terbuang.

---

## File
```
BARU    database/migrations/..._create_site_pages_table.php
BARU    database/migrations/..._add_images_to_site_pages.php
BARU    app/Models/SitePage.php
BARU    app/Http/Controllers/Admin/SitePageController.php
BARU    resources/views/admin/sitepages/index.blade.php
BARU    resources/views/sitepage.blade.php
DIUBAH  app/Http/Controllers/PageController.php    (token gambar situs + pencocokan topik)
```

Setelah pasang:
```bash
php artisan migrate
php artisan view:clear && php artisan cache:clear
```

---

# Paket Halaman per Jenis Usaha

Halaman statis **sengaja tidak dibuat saat instalasi** — tiap produk punya
struktur menu berbeda. Setelah domain terpasang, operator memilih paket yang
sesuai lalu menyuntingnya.

## Cara pakai

Buka **Admin → Halaman Statis**. Selama belum ada halaman sama sekali, muncul
kartu **⚡ Muat Paket Halaman**. Pilih satu, halaman langsung terbuat.

| Paket | Untuk | Halaman yang dibuat |
|---|---|---|
| **Jasa Umum** | Servis, perbaikan, konsultan, kontraktor | Tentang · Layanan · Cara Pesan · Kontak |
| **Produk / Toko** | Herbal, kosmetik, makanan, barang fisik | Tentang · Produk · Cara Order · Pengiriman · Kontak |
| **Properti** | Agen, developer, sewa, kos | Tentang · Properti · Proses · Kontak |
| **Pendidikan / Kursus** | Bimbel, les privat, pelatihan | Tentang · Program · Cara Daftar · Kontak |
| **Kesehatan / Klinik** | Klinik, terapi, perawatan | Tentang · Layanan · Jadwal · Kontak |
| **Minimal** | Situs sederhana | Tentang · Kontak |

## Yang perlu diperhatikan

**Isinya kerangka, bukan konten jadi.** Tiap halaman berisi struktur + penanda
seperti *"Sunting bagian ini dengan cerita bisnis Anda yang sebenarnya"* dan
kolom kosong (alamat, jam operasional, ekspedisi). **Wajib disunting** sebelum
situs dipublikasikan — menayangkan kerangka apa adanya membuat situs terlihat
belum siap dan tidak memberi informasi apa pun ke pengunjung.

**Tidak menimpa.** Slug yang sudah ada — baik sebagai halaman statis maupun
sebagai layanan pSEO — otomatis dilewati, dan dilaporkan di pesan hasil.

**Gambar otomatis.** Preset sudah memakai token `{{situs_tentang}}`,
`{{situs_keunggulan}}`, `{{situs_proses}}`, `{{situs_galeri}}` — jadi begitu
gambar diisi di Pengaturan, halaman-halaman ini langsung bergambar tanpa
diapa-apakan.

**Catatan untuk paket Kesehatan:** isi bawaannya mengingatkan agar tidak
menjanjikan kesembuhan atau hasil pasti. Klaim seperti itu berisiko secara hukum
dan melanggar kebijakan iklan.

## Alur instalasi klien baru (lengkap)

```
1. pasang-situs-baru.sh domain.com "Nama Brand"
2. Login admin → Pengaturan: brand, WhatsApp, warna, logo, gambar
3. Halaman Statis → Muat Paket sesuai jenis usaha → SUNTING isinya
4. Mulai Cepat → kata kunci + wilayah → konten & halaman pSEO
5. Periksa sampel → publish bertahap
6. Search Console: verifikasi + submit sitemap
```

## File tambahan
```
BARU    app/Support/PagePresets.php
DIUBAH  app/Http/Controllers/Admin/SitePageController.php  (aksi loadPreset)
DIUBAH  resources/views/admin/sitepages/index.blade.php    (pemilih paket)
DIUBAH  routes/web.php
```
