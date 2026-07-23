<?php

namespace App\Services\Ai;

use App\Models\ContentBlock;
use App\Models\Faq;
use App\Models\LeadClick;
use App\Models\Page;
use App\Models\Setting;
use App\Services\ContentHealthService;
use App\Services\IndexNowService;
use App\Services\SearchConsole\SearchConsoleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "INDRA" asisten SEO: mengumpulkan kondisi nyata situs jadi satu snapshot.
 *
 * Ini yang membedakan asisten ini dari chatbot biasa — ia tidak menebak, tapi
 * membaca angka sungguhan: stok konten, jumlah halaman, halaman tipis,
 * performa Search Console, klik WhatsApp, dan kelengkapan konfigurasi.
 *
 * Snapshot dipakai untuk: (a) laporan kesehatan ke owner, (b) konteks yang
 * disuntikkan ke prompt AI agar sarannya spesifik & berbasis data.
 */
class SiteAuditService
{
    public function __construct(private readonly ContentHealthService $health)
    {
    }

    /**
     * Snapshot lengkap kondisi situs.
     *
     * @return array<string,mixed>
     */
    public function snapshot(): array
    {
        return [
            'identitas' => $this->identity(),
            'konten' => $this->content(),
            'halaman' => $this->pages(),
            'keunikan' => $this->uniqueness(),
            'lead' => $this->leads(),
            'search_console' => $this->searchConsole(),
            'konfigurasi' => $this->config(),
            'masalah' => [], // diisi di bawah
        ];
    }

    /**
     * Snapshot + daftar masalah terdeteksi (prioritas tertinggi dulu).
     *
     * @return array<string,mixed>
     */
    public function fullAudit(): array
    {
        $snap = $this->snapshot();
        $snap['masalah'] = $this->detectIssues($snap);
        $snap['skor'] = $this->overallScore($snap);
        $snap['tugas_owner'] = $this->ownerTasks($snap);

        return $snap;
    }

    /**
     * TUGAS YANG TIDAK BISA DIKERJAKAN AI — mutlak butuh owner.
     *
     * Pembagian kerja mesin ini: AI mengerjakan yang bisa diotomatiskan
     * (menulis kalimat, membuat keyword, memicu proses), lalu MELAPOR ke owner
     * untuk hal-hal yang secara teknis/etis hanya bisa dilakukan manusia:
     *   - akses ke rahasia (kunci API, file .env di server)
     *   - klaim kepemilikan (verifikasi Search Console)
     *   - fakta bisnis nyata (nomor WA, harga asli, testimoni riil)
     *   - perintah sistem/server (queue worker, deploy)
     *   - keputusan strategis berisiko (publish massal)
     *
     * @param array<string,mixed> $s
     * @return array<int,array{prioritas:string,tugas:string,kenapa:string,cara:string}>
     */
    private function ownerTasks(array $s): array
    {
        $t = [];
        $cfg = $s['konfigurasi'];

        if (! $s['identitas']['whatsapp_terisi']) {
            $t[] = [
                'prioritas' => 'kritis',
                'tugas' => 'Isi nomor WhatsApp bisnis.',
                'kenapa' => 'AI tidak tahu nomor asli Anda. Tanpa ini semua halaman tidak bisa menghasilkan chat.',
                'cara' => 'Pengaturan → Nomor WhatsApp.',
            ];
        }

        if (! $cfg['ai_aktif']) {
            $t[] = [
                'prioritas' => 'kritis',
                'tugas' => 'Pasang kunci API AI.',
                'kenapa' => 'Kunci berbayar & rahasia — hanya boleh Anda yang memasukkannya ke server.',
                'cara' => 'Isi AI_DRIVER, AI_API_KEY, AI_MODEL di file .env, lalu jalankan: php artisan config:clear',
            ];
        }

        if (! $cfg['verifikasi_google']) {
            $t[] = [
                'prioritas' => 'tinggi',
                'tugas' => 'Verifikasi situs di Google Search Console.',
                'kenapa' => 'Google harus memastikan Anda pemilik domain — AI tidak bisa mengklaim itu.',
                'cara' => 'Buka Search Console → metode "Tag HTML" → salin kodenya ke Pengaturan → Integrasi Google → Simpan → klik Verify.',
            ];
        }

        if (($s['keunikan']['tipis_persen'] ?? 0) >= 50 && ($s['halaman']['published'] ?? 0) > 0) {
            $t[] = [
                'prioritas' => 'tinggi',
                'tugas' => 'Isi data lokal riil di CSV (harga, jumlah, landmark).',
                'kenapa' => 'AI bisa menulis kalimat, tapi tidak tahu harga & fakta asli bisnis Anda. Ini pembeda utama antar halaman.',
                'cara' => 'Tambahkan kolom data lokal yang relevan dengan bisnis Anda di CSV (mis. harga, jadwal, landmark) — minimal 2 kolom terisi per baris.',
            ];
        }

        if (! $cfg['indexnow_aktif'] && ($s['halaman']['published'] ?? 0) > 100) {
            $t[] = [
                'prioritas' => 'sedang',
                'tugas' => 'Aktifkan IndexNow.',
                'kenapa' => 'Perlu menulis kunci ke file .env di server — di luar jangkauan AI.',
                'cara' => 'Buat kunci acak (openssl rand -hex 16), isi INDEXNOW_KEY di .env, lalu php artisan config:clear',
            ];
        }

        if (! $cfg['analytics_aktif']) {
            $t[] = [
                'prioritas' => 'sedang',
                'tugas' => 'Pasang Google Analytics / GTM.',
                'kenapa' => 'Akun Analytics milik Anda; ID-nya harus diambil dari akun tersebut.',
                'cara' => 'Salin Measurement ID (G-XXXXXXX) ke Pengaturan → Integrasi Google.',
            ];
        }

        if (! $cfg['og_image_terisi']) {
            $t[] = [
                'prioritas' => 'rendah',
                'tugas' => 'Unggah gambar untuk preview media sosial.',
                'kenapa' => 'Butuh aset visual milik bisnis Anda (logo/foto).',
                'cara' => 'Isi og_image di Pengaturan.',
            ];
        }

        // Selalu: keputusan strategis & operasional server.
        $t[] = [
            'prioritas' => 'info',
            'tugas' => 'Pastikan worker antrian berjalan di server.',
            'kenapa' => 'Proses generate/publish/AI berjalan di antrian. AI tidak punya akses shell server.',
            'cara' => 'Jalankan php artisan queue:work (produksi: pakai Supervisor, lihat docs/PRODUKSI.md).',
        ];

        $t[] = [
            'prioritas' => 'info',
            'tugas' => 'Keputusan publish massal tetap di tangan Anda.',
            'kenapa' => 'Berdampak besar & sulit dibatalkan. AI hanya memberi rekomendasi, bukan memutuskan.',
            'cara' => 'Publish bertahap dari dashboard — mulai satu kota, ukur, baru meluas.',
        ];

        return $t;
    }

    // -----------------------------------------------------------------
    // Bagian-bagian snapshot
    // -----------------------------------------------------------------

    /** @return array<string,mixed> */
    private function identity(): array
    {
        return [
            'brand' => (string) Setting::get('brand_name', ''),
            'tagline' => (string) Setting::get('tagline', ''),
            'domain' => url('/'),
            'whatsapp_terisi' => trim((string) Setting::get('whatsapp_number', '')) !== '',
        ];
    }

    /** @return array<string,mixed> */
    private function content(): array
    {
        $h = $this->health->health();

        $kurang = [];
        foreach ($h['sections'] as $s) {
            if (! $s['ok']) {
                $kurang[] = [
                    'section' => $s['section'],
                    'punya' => $s['count'],
                    'target' => $s['target'],
                    'kurang' => max(0, $s['target'] - $s['count']),
                ];
            }
        }

        return [
            'skor_stok' => $h['score'],
            'semua_hijau' => $h['all_ok'],
            'total_variasi' => (int) ContentBlock::where('is_active', true)->count(),
            'total_faq' => (int) Faq::where('is_active', true)->count(),
            'faq_ok' => $h['faq']['ok'] ?? false,
            'section_kurang' => $kurang,
        ];
    }

    /** @return array<string,mixed> */
    private function pages(): array
    {
        $total = (int) Page::count();
        $published = (int) Page::published()->count();
        $draft = (int) Page::draft()->count();

        // Sebaran per kota (top 10) untuk melihat konsentrasi.
        $perKota = [];
        if ($total > 0) {
            $perKota = Page::published()
                ->join('cities', 'cities.id', '=', 'pages.city_id')
                ->select('cities.name', DB::raw('count(*) as total'))
                ->groupBy('cities.name')
                ->orderByDesc('total')
                ->limit(10)
                ->pluck('total', 'name')
                ->toArray();
        }

        return [
            'total' => $total,
            'published' => $published,
            'draft' => $draft,
            'jumlah_kota_aktif' => count($perKota),
            'top_kota' => $perKota,
        ];
    }

    /**
     * Sampling halaman tipis (tanpa memindai jutaan baris).
     *
     * @return array<string,mixed>
     */
    private function uniqueness(): array
    {
        $min = (int) config('daya.thin_min_local_facts', 2);
        $sample = Page::published()->inRandomOrder()->limit(200)->get(['id', 'extra']);

        if ($sample->isEmpty()) {
            return ['sampel' => 0, 'tipis_persen' => 0, 'ambang_fakta' => $min];
        }

        $thin = 0;
        foreach ($sample as $p) {
            $extra = is_array($p->extra) ? $p->extra : [];
            $facts = 0;
            foreach ($extra as $k => $v) {
                if (in_array(strtolower((string) $k), ['lat', 'lng', 'latitude', 'longitude'], true)) {
                    continue;
                }
                if (is_scalar($v) && trim((string) $v) !== '') {
                    $facts++;
                }
            }
            if ($facts < $min) {
                $thin++;
            }
        }

        return [
            'sampel' => $sample->count(),
            'tipis_persen' => (int) round($thin / $sample->count() * 100),
            'ambang_fakta' => $min,
        ];
    }

    /** @return array<string,mixed> */
    private function leads(): array
    {
        if (! Schema::hasTable('lead_clicks')) {
            return ['tersedia' => false];
        }

        $since = now()->subDays(30);

        return [
            'tersedia' => true,
            'klik_30_hari' => (int) LeadClick::where('created_at', '>=', $since)->count(),
            'klik_7_hari' => (int) LeadClick::where('created_at', '>=', now()->subDays(7))->count(),
            'top_halaman' => LeadClick::where('created_at', '>=', $since)
                ->select('page_path', DB::raw('count(*) as total'))
                ->groupBy('page_path')->orderByDesc('total')->limit(5)
                ->pluck('total', 'page_path')->toArray(),
            'top_kota' => LeadClick::where('created_at', '>=', $since)
                ->whereNotNull('city')
                ->select('city', DB::raw('count(*) as total'))
                ->groupBy('city')->orderByDesc('total')->limit(5)
                ->pluck('total', 'city')->toArray(),
        ];
    }

    /** @return array<string,mixed> */
    private function searchConsole(): array
    {
        if (! SearchConsoleService::isConfigured()) {
            return ['terhubung' => false];
        }

        try {
            $s = app(SearchConsoleService::class)->summary(28);

            return ['terhubung' => true] + $s;
        } catch (\Throwable $e) {
            return ['terhubung' => true, 'error' => $e->getMessage()];
        }
    }

    /** @return array<string,bool|string> */
    private function config(): array
    {
        return [
            'ai_aktif' => AiClientFactory::isConfigured(),
            'indexnow_aktif' => IndexNowService::isEnabled(),
            'gsc_terhubung' => SearchConsoleService::isConfigured(),
            'verifikasi_google' => trim((string) Setting::get('google_site_verification', '')) !== '',
            'analytics_aktif' => trim((string) Setting::get('google_analytics_id', '')) !== ''
                || trim((string) Setting::get('gtm_id', '')) !== '',
            'og_image_terisi' => trim((string) Setting::get('og_image', '') ?: (string) Setting::get('hero_image', '')) !== '',
            'robots' => (string) Setting::get('default_robots', 'index,follow'),
        ];
    }

    // -----------------------------------------------------------------
    // Deteksi masalah & skor
    // -----------------------------------------------------------------

    /**
     * @param array<string,mixed> $s
     * @return array<int,array{prioritas:string,masalah:string,dampak:string,aksi:string}>
     */
    private function detectIssues(array $s): array
    {
        $out = [];

        if (! $s['identitas']['whatsapp_terisi']) {
            $out[] = [
                'prioritas' => 'kritis',
                'masalah' => 'Nomor WhatsApp belum diisi.',
                'dampak' => 'Semua halaman tidak bisa menghasilkan lead — tombol utama tidak berfungsi.',
                'aksi' => 'Isi nomor WhatsApp di Pengaturan.',
            ];
        }

        if (! $s['konten']['semua_hijau']) {
            $kurang = count($s['konten']['section_kurang']);
            $out[] = [
                'prioritas' => 'tinggi',
                'masalah' => "Stok variasi konten belum lengkap ({$kurang} bagian di bawah target, skor {$s['konten']['skor_stok']}/100).",
                'dampak' => 'Halaman jadi terlalu mirip satu sama lain — risiko dianggap konten massal.',
                'aksi' => 'Jalankan "Isi Otomatis dengan AI" di menu Variasi Konten.',
            ];
        }

        if (($s['keunikan']['tipis_persen'] ?? 0) >= 50 && ($s['halaman']['published'] ?? 0) > 0) {
            $out[] = [
                'prioritas' => 'tinggi',
                'masalah' => "Sekitar {$s['keunikan']['tipis_persen']}% halaman tergolong tipis (kurang data lokal riil).",
                'dampak' => 'Halaman tipis rawan tidak terindeks atau dianggap duplikat oleh Google.',
                'aksi' => 'Tambahkan kolom data lokal (harga, jumlah, landmark) di CSV, minimal 2 per baris.',
            ];
        }

        if (! $s['konfigurasi']['verifikasi_google']) {
            $out[] = [
                'prioritas' => 'tinggi',
                'masalah' => 'Situs belum diverifikasi di Google Search Console.',
                'dampak' => 'Tidak bisa memantau indexing, dan sitemap tidak bisa disubmit.',
                'aksi' => 'Tempel kode verifikasi di Pengaturan → Integrasi Google.',
            ];
        }

        if (! $s['konfigurasi']['indexnow_aktif'] && ($s['halaman']['published'] ?? 0) > 100) {
            $out[] = [
                'prioritas' => 'sedang',
                'masalah' => 'IndexNow belum aktif.',
                'dampak' => 'Halaman baru lebih lambat terindeks (Bing/Yandex).',
                'aksi' => 'Isi INDEXNOW_KEY di .env (string hex acak).',
            ];
        }

        if (! $s['konfigurasi']['analytics_aktif']) {
            $out[] = [
                'prioritas' => 'sedang',
                'masalah' => 'Google Analytics/GTM belum dipasang.',
                'dampak' => 'Tidak ada data perilaku pengunjung untuk mengukur konversi.',
                'aksi' => 'Isi Measurement ID di Pengaturan → Integrasi Google.',
            ];
        }

        if (($s['halaman']['published'] ?? 0) === 0 && ($s['halaman']['total'] ?? 0) > 0) {
            $out[] = [
                'prioritas' => 'tinggi',
                'masalah' => 'Ada halaman ter-generate tapi belum ada yang dipublish.',
                'dampak' => 'Situs belum menghasilkan trafik sama sekali.',
                'aksi' => 'Publish bertahap dari dashboard (mulai satu kota).',
            ];
        }

        if (($s['halaman']['published'] ?? 0) > 5000 && ($s['halaman']['jumlah_kota_aktif'] ?? 0) <= 1) {
            $out[] = [
                'prioritas' => 'sedang',
                'masalah' => 'Banyak halaman terkonsentrasi di satu kota.',
                'dampak' => 'Peluang ekspansi wilayah belum dimanfaatkan.',
                'aksi' => 'Setelah indexing kota pertama sehat, ekspor wilayah berikutnya.',
            ];
        }

        if (! $s['konfigurasi']['og_image_terisi']) {
            $out[] = [
                'prioritas' => 'rendah',
                'masalah' => 'Gambar Open Graph belum diisi.',
                'dampak' => 'Tautan yang dibagikan di WhatsApp/medsos tampil tanpa gambar (CTR turun).',
                'aksi' => 'Isi og_image di Pengaturan.',
            ];
        }

        if (str_contains($s['konfigurasi']['robots'], 'noindex')) {
            $out[] = [
                'prioritas' => 'kritis',
                'masalah' => 'Setelan robots masih "noindex" — Google diminta TIDAK mengindeks situs.',
                'dampak' => 'Situs tidak akan muncul di hasil pencarian sama sekali.',
                'aksi' => 'Ubah default_robots menjadi index,follow di Pengaturan.',
            ];
        }

        return $out;
    }

    /**
     * Skor kesehatan keseluruhan 0–100 (gabungan stok konten, konfigurasi,
     * keunikan, dan status publish).
     *
     * @param array<string,mixed> $s
     */
    private function overallScore(array $s): int
    {
        $score = 0;

        // Stok konten: 30 poin.
        $score += (int) round(($s['konten']['skor_stok'] ?? 0) * 0.30);

        // Konfigurasi penting: 30 poin.
        $cfg = $s['konfigurasi'];
        $flags = [
            $s['identitas']['whatsapp_terisi'],
            $cfg['verifikasi_google'],
            $cfg['analytics_aktif'],
            $cfg['indexnow_aktif'],
            $cfg['og_image_terisi'],
            ! str_contains($cfg['robots'], 'noindex'),
        ];
        $score += (int) round(count(array_filter($flags)) / count($flags) * 30);

        // Keunikan: 25 poin (makin sedikit halaman tipis makin baik).
        $thin = (int) ($s['keunikan']['tipis_persen'] ?? 100);
        $score += (int) round((100 - $thin) / 100 * 25);

        // Sudah ada halaman published: 15 poin.
        $score += ($s['halaman']['published'] ?? 0) > 0 ? 15 : 0;

        return max(0, min(100, $score));
    }
}
