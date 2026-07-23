# ✅ CHECKLIST RILIS v1.0 — Baca Ini Terakhir, Kerjakan Berurutan

**Vonis kesiapan (12 Juli 2026):** Source code SIAP RILIS untuk peluncuran
produksi terkendali — 19 test otomatis lolos, arsitektur teruji untuk jutaan
baris, keamanan dasar tertutup (login throttled, output di-escape, kredensial
default dicegat otomatis di production, admin ter-autentikasi). "Siap rilis"
BUKAN berarti "tinggal gas 2 juta halaman" — kesiapan kode ≠ kesiapan konten;
ikuti gelombang di PANDUAN-OPERATOR & PANDUAN-DATA-CSV.

---

## A. Syarat server (sebelum apa pun)

- [ ] **PHP 8.3 atau lebih baru** — composer.json dipatok ke platform 8.3
      (kompatibel dengan bawaan FastPanel). Bila lock lama masih menuntut
      8.4, jalankan sekali: `composer update --no-dev` lalu commit lock barunya.
- [ ] MySQL/MariaDB, Redis, Supervisor terpasang; nginx/FastPanel siap.
- [ ] Domain mengarah ke server, SSL siap diaktifkan.

## B. Instalasi per situs (pilih satu jalur)

- [ ] **Jalur skrip** (VPS tanpa panel): `pasang-situs-baru.sh <domain> "<Brand>"`
      — sudah otomatis: DB acak, APP_NAME unik, kredensial admin acak
      (dicetak di akhir — CATAT), nginx + cache, worker, SSL.
- [ ] **Jalur FastPanel**: buat situs (document root → `public/`), buat DB,
      clone repo, lalu WAJIB di `.env`: `APP_ENV=production`,
      `APP_DEBUG=false`, `APP_URL=https://domain`, **`APP_NAME` unik**,
      `ADMIN_EMAIL` + `ADMIN_PASSWORD` kuat, DB creds, Redis untuk
      CACHE/QUEUE/SESSION. Lalu: `composer install --no-dev` →
      `php artisan key:generate` → `migrate --seed --force` →
      `config:cache` → pasang worker supervisor (contoh di `deploy/`).

## C. Verifikasi 10 menit setelah pasang

- [ ] `https://domain/admin` terbuka; login dengan kredensial BARU (bukan
      default — kalau default masih bisa masuk, BERHENTI dan perbaiki .env).
- [ ] `php artisan test` di server → 19 lolos.
- [ ] Buka satu URL publik → view-source: class CSS BUKAN `cegu-*`
      (sidik jari aktif), ada JSON-LD `Service` + `areaServed`.
- [ ] `curl -I` salespage → `Cache-Control: max-age=600, public`.
- [ ] `/sitemap.xml` valid; `/robots.txt` mengizinkan crawl.
- [ ] Upload CSV kecil (5 baris) → generate jalan (worker hidup) → publish 1
      → tombol WA benar nomor & pesannya.

## D. Sebelum halaman pertama tayang (konten)

- [ ] Pengaturan: brand, tagline, WA, organization, hero image — khas situs ini.
- [ ] Muat Paket Konten sesuai niche → tulis ulang/perbanyak sampai Dashboard
      berkata **SIAP GENERATE MASSAL**.
- [ ] CSV disusun sesuai PANDUAN-DATA-CSV (aturan keyword: produk ≠ modifier).
- [ ] Google Search Console terverifikasi; sitemap disubmit setelah publish
      pertama.

## E. Operasional sejak hari-1

- [ ] Backup malam otomatis: `mysqldump` + `.env` (cron).
- [ ] Publish BERTAHAP per gelombang; pantau GSC mingguan (rasio index +
      Manual Actions).
- [ ] Repo private + tag `v1.0` sebelum situs kedua; update via
      `update-semua-situs.sh`.

## F. Jujur: yang BELUM ada (bukan blocker, jangan tunda rilis karenanya)

1. **Variasi konten per-layanan** (seperti halaman Calistung benchmark) —
   peningkatan kualitas terbesar berikutnya; sementara ini kompensasi dengan
   FAQ per-layanan + pool besar.
2. Dashboard rekap lintas-situs terpusat — mulai saja dengan spreadsheet.
3. IndexNow/Indexing API — nanti saat skala ratusan ribu halaman.
4. 2FA admin — mitigasi sekarang: password acak kuat + jangan share.

Aturan terakhir: **rilis diukur dari situs #1 yang menghasilkan lead — bukan
dari jumlah fitur.** Kode sudah cukup; sisanya eksekusi dan kesabaran.
