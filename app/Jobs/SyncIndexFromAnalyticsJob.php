<?php

namespace App\Jobs;

use App\Models\Page;
use App\Models\PageIndexStatus;
use App\Services\SearchConsole\SearchConsoleService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

/**
 * Menandai halaman TERINDEKS berdasarkan data Search Analytics.
 *
 * Kenapa penting: halaman yang pernah muncul di hasil pencarian PASTI sudah
 * terindeks. Search Analytics API mengembalikan hingga 25.000 baris per
 * panggilan dan bisa dipaginasi — sehingga ratusan ribu halaman bisa dipetakan
 * TANPA menyentuh kuota URL Inspection (2.000/hari).
 *
 * Hasilnya: daftar "belum terindeks" terbentuk otomatis sebagai selisih antara
 * halaman published dan halaman yang terbukti tampil di pencarian — siap
 * ditindaklanjuti owner.
 *
 * Catatan kejujuran: halaman yang sudah terindeks tetapi belum pernah muncul di
 * pencarian akan ikut masuk daftar "belum terindeks". Karena itu daftar ini
 * bersifat KANDIDAT, dan URL Inspection tetap dipakai untuk memastikan.
 */
class SyncIndexFromAnalyticsJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;
    public int $tries = 1;

    private const PROGRESS_KEY = 'gsc:sync:progress';

    public function __construct(public int $days = 90)
    {
    }

    public function handle(SearchConsoleService $gsc): void
    {
        if (! SearchConsoleService::isConfigured()) {
            $this->progress('error', 'Search Console belum dikonfigurasi.');

            return;
        }

        $this->progress('running', 'Mengambil data pencarian…');

        try {
            $data = $gsc->indexedByAnalytics($this->days);
        } catch (\Throwable $e) {
            $this->progress('error', 'Gagal mengambil data: ' . $e->getMessage());

            return;
        }

        $urls = $data['urls'] ?? [];
        if ($urls === []) {
            $this->progress('done', 'Belum ada halaman yang muncul di hasil pencarian. Wajar bila situs masih baru.');

            return;
        }

        // Ubah URL penuh menjadi path agar cocok dengan kolom pages.path.
        $paths = [];
        foreach ($urls as $url) {
            $path = ltrim((string) parse_url($url, PHP_URL_PATH), '/');
            if ($path !== '') {
                $paths[$path] = true;
            }
        }

        $this->progress('running', 'Mencocokkan ' . count($paths) . ' halaman…');

        $ditandai = 0;
        Page::published()
            ->select('id', 'path')
            ->chunkById(1000, function ($pages) use (&$paths, &$ditandai) {
                foreach ($pages as $page) {
                    if (! isset($paths[$page->path])) {
                        continue;
                    }

                    PageIndexStatus::updateOrCreate(
                        ['page_id' => $page->id],
                        [
                            'verdict' => 'PASS',
                            'coverage_state' => 'Submitted and indexed',
                            'source' => 'analytics',
                            'checked_at' => now(),
                            'error' => null,
                        ],
                    );
                    $ditandai++;
                }
            });

        $published = (int) Page::published()->count();
        $belum = max(0, $published - $ditandai);

        $this->progress('done', "Selesai. {$ditandai} halaman terbukti terindeks, {$belum} halaman belum terbukti.");
    }

    private function progress(string $status, string $message): void
    {
        Cache::put(self::PROGRESS_KEY, ['status' => $status, 'message' => $message], 3600);
    }

    /**
     * @return array<string,mixed>
     */
    public static function state(): array
    {
        return Cache::get(self::PROGRESS_KEY, ['status' => 'idle', 'message' => '']);
    }
}
