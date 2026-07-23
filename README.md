# DAYA AI — Mesin Programmatic SEO

Mesin pSEO untuk bisnis lokal Indonesia. Satu instalasi melayani satu bisnis,
menghasilkan halaman penjualan per-wilayah dari perkalian **layanan × lokasi**,
dengan konten bervariasi otomatis agar tiap halaman tetap berbeda.

**Stack:** Laravel 12 · PHP 8.4 · MySQL · Redis · Nginx · Supervisor

---

## Kemampuan

| Bidang | Fitur |
|---|---|
| **Produksi halaman** | Cross-join layanan × wilayah, variasi deterministik, render on-the-fly |
| **Data wilayah** | 91.162 wilayah resmi Indonesia (provinsi→kelurahan) + koordinat 551 kota |
| **AI** | Isi pool konten, generator keyword longtail, Asisten SEO berbasis data situs |
| **SEO** | Meta lengkap + Open Graph, schema LocalBusiness/FAQ/Breadcrumb, sitemap ber-index, canonical anti-halaman-tipis |
| **Indexing** | IndexNow, monitoring Search Console, daftar kerja halaman belum terindeks |
| **Konversi** | Rotator nomor WhatsApp, pelacakan klik per nomor CS, statistik harian/bulanan/tahunan |
| **Situs** | Halaman statis (Tentang/Layanan/Kontak) dengan menu navigasi, 12 template kerangka copywriting |
| **Multi-klien** | Warna & gambar per instalasi, skrip pasang domain baru, penyebaran update |

## Instalasi cepat (pengembangan)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed        # hanya membuat akun admin
php artisan serve
```

Buka `/admin`. Kredensial admin diatur lewat `ADMIN_EMAIL` & `ADMIN_PASSWORD` di `.env`.

> Data contoh **tidak** ikut. Untuk demo: `php artisan db:seed --class=Database\\Seeders\\DemoSeeder`

## Pemasangan produksi (satu domain)

```bash
git clone <repo> /opt/daya-engine
sudo AI_API_KEY="sk-or-v1-xxx" \
  bash /opt/daya-engine/deploy/pasang-situs-baru.sh domain.com "Nama Brand"
```

Skrip menyiapkan: direktori, database, `.env` (termasuk IndexNow key unik),
Nginx + micro-cache, worker Supervisor, SSL, dan cron. Lihat
[`docs/SKEMA-MULTI-SITUS.md`](docs/SKEMA-MULTI-SITUS.md).

## Alur pemakaian

```
1. Pengaturan  → nama brand, NOMOR WHATSAPP, warna, logo, gambar
2. Halaman Statis → muat paket sesuai jenis usaha, sunting isinya
3. Mulai Cepat → kata kunci + wilayah → konten & halaman terisi otomatis
4. Periksa 5–10 sampel → publish bertahap
5. Search Console → verifikasi + submit sitemap
6. Pantau panel Lead & Indexing → perluas berdasarkan data
```

## Konfigurasi penting

```env
APP_NAME="Nama Brand"        # WAJIB unik per situs (prefix cache/queue Redis)
DAYA_ENGINE_NAME="DAYA AI"

AI_DRIVER=openrouter         # openrouter | anthropic | openai | deepseek
AI_API_KEY=
AI_MODEL=anthropic/claude-sonnet-5

INDEXNOW_KEY=                # openssl rand -hex 16 (unik per domain)
GSC_CREDENTIALS=             # service account JSON (opsional)
GSC_SITE_URL=
```

Semua fitur AI/IndexNow/GSC bersifat **opsional** — aplikasi tetap berjalan
normal bila kuncinya kosong.

## Dokumentasi

| Topik | Berkas |
|---|---|
| Operator awam | [`docs/PANDUAN-OPERATOR-AWAM.md`](docs/PANDUAN-OPERATOR-AWAM.md) |
| Produksi & deploy | [`docs/PRODUKSI.md`](docs/PRODUKSI.md) |
| Multi-domain / multi-server | [`docs/SKEMA-MULTI-SITUS.md`](docs/SKEMA-MULTI-SITUS.md) |
| Mulai Cepat (wizard) | [`docs/MULAI-CEPAT.md`](docs/MULAI-CEPAT.md) |
| Integrasi AI | [`docs/AI-INTEGRATION.md`](docs/AI-INTEGRATION.md) |
| Otak MD asisten | [`docs/OTAK-MD.md`](docs/OTAK-MD.md) · [`docs/PROMPT-CACHING.md`](docs/PROMPT-CACHING.md) |
| Indexing & kuota | [`docs/ALUR-INDEXING.md`](docs/ALUR-INDEXING.md) · [`docs/KUOTA-INSPEKSI.md`](docs/KUOTA-INSPEKSI.md) |
| Template kerangka | [`docs/TEMPLATE-KERANGKA.md`](docs/TEMPLATE-KERANGKA.md) · `resources/templates/` |
| Halaman statis & menu | [`docs/HALAMAN-STATIS.md`](docs/HALAMAN-STATIS.md) |
| Multi-produk (warna/gambar) | [`docs/MULTI-PRODUK.md`](docs/MULTI-PRODUK.md) |
| **Aturan menambah fitur** | [`docs/ATURAN-TAMBAH-FITUR.md`](docs/ATURAN-TAMBAH-FITUR.md) |

## Perintah CLI

```bash
php artisan locations:export --city="Depok" --with-coords --out=depok.csv
php artisan keywords:generate "les privat" --count=200 --out=keyword.csv
php artisan indexnow:submit --limit=10000
php artisan gsc:stats --days=28
php artisan pages:warm --limit=2000
```

---

## Catatan penting sebelum produksi

**Skalakan bertahap.** Kebijakan *scaled content abuse* Google menilai halaman
dari nilainya bagi pengguna, bukan dari cara pembuatannya. Halaman massal aman
selama tiap halaman punya pembeda nyata — isi kolom data lokal (harga, jadwal,
landmark) di CSV, jangan andalkan variasi kalimat saja.

Publikasikan satu kota dulu, pantau indexing 1–2 minggu, baru meluas. Bila banyak
halaman berstatus *"Discovered – currently not indexed"*, hentikan penambahan dan
perkaya isinya.

**Jangan gunakan testimoni karangan.** Menayangkan testimoni buatan AI seolah
pengalaman pelanggan nyata itu menyesatkan dan melanggar kebijakan Google.
Kumpulkan yang asli, atau kosongkan bagian itu.

## Keamanan

- `.env` dan kredensial JSON dilindungi `.gitignore` — jangan pernah di-commit.
- `DAYA_TEMPLATE_BLADE=false` (bawaan). Menyalakannya berarti mengizinkan
  eksekusi PHP lewat template — hanya untuk operator tepercaya, dan butuh dua
  saklar sekaligus (`.env` + Pengaturan).
- Halaman publik dibatasi laju per IP (`DAYA_PUBLIC_RATE_LIMIT`).
