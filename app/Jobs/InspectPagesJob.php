<?php

namespace App\Jobs;

use App\Models\Page;
use App\Models\PageIndexStatus;
use App\Services\SearchConsole\UrlInspectionService;
use App\Support\AiFillProgress;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

/**
 * Periksa status indexing sejumlah halaman ke Google (URL Inspection API).
 *
 * SANGAT MEMPERHATIKAN KUOTA: Google membatasi 2.000 inspeksi/hari dan
 * 600/menit per properti. Job ini berhenti sendiri saat kuota menipis, dan
 * memberi jeda antar permintaan agar tidak menembus batas per menit.
 *
 * Halaman yang sudah pernah diperiksa dalam N hari terakhir dilewati, supaya
 * kuota dipakai untuk halaman yang benar-benar belum diketahui statusnya.
 */
class InspectPagesJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;
    public int $tries = 1;

    private const PROGRESS_KEY = 'gsc:inspect:progress';

    public function __construct(
        public int $limit = 100,
        public int $recheckAfterDays = 14,
        public bool $sampling = false,
    ) {
    }

    public function handle(UrlInspectionService $inspector): void
    {
        $limit = max(1, min(2000, $this->limit));
        $sisa = UrlInspectionService::remainingToday();

        if ($sisa <= 0) {
            Cache::put(self::PROGRESS_KEY, [
                'status' => 'error',
                'message' => 'Kuota inspeksi harian Google sudah habis. Coba lagi besok.',
                'done' => 0, 'total' => 0,
            ], 3600);

            return;
        }

        $limit = min($limit, $sisa);

        // MODE SAMPLING: untuk situs sangat besar, memeriksa SEMUA halaman itu
        // boros kuota dan tidak perlu. Sampel acak beberapa ratus halaman sudah
        // cukup mewakili kondisi keseluruhan secara statistik.
        if ($this->sampling) {
            $pages = Page::published()
                ->inRandomOrder()
                ->limit($limit)
                ->get();
        } else {
            // Prioritas: halaman published yang BELUM pernah diperiksa, lalu yang
            // pemeriksaannya sudah kedaluwarsa.
            $batas = now()->subDays(max(1, $this->recheckAfterDays));

            $pages = Page::published()
                ->leftJoin('page_index_statuses as s', 's.page_id', '=', 'pages.id')
                ->where(function ($q) use ($batas) {
                    $q->whereNull('s.id')->orWhere('s.checked_at', '<', $batas);
                })
                ->orderByRaw('s.checked_at IS NOT NULL, s.checked_at ASC')
                ->limit($limit)
                ->select('pages.*')
                ->get();
        }

        $total = $pages->count();
        $done = 0;
        $gagal = 0;

        Cache::put(self::PROGRESS_KEY, [
            'status' => 'running', 'message' => "Memeriksa {$total} halaman…",
            'done' => 0, 'total' => $total,
        ], 3600);

        foreach ($pages as $page) {
            if (UrlInspectionService::remainingToday() <= 0) {
                break;
            }

            try {
                $inspector->inspect($page);
            } catch (\Throwable $e) {
                $gagal++;
                if ($gagal >= 5) {
                    Cache::put(self::PROGRESS_KEY, [
                        'status' => 'error',
                        'message' => 'Berhenti setelah 5 kegagalan: ' . $e->getMessage(),
                        'done' => $done, 'total' => $total,
                    ], 3600);

                    return;
                }
            }

            $done++;
            if ($done % 5 === 0) {
                Cache::put(self::PROGRESS_KEY, [
                    'status' => 'running',
                    'message' => "Memeriksa… {$done}/{$total}",
                    'done' => $done, 'total' => $total,
                ], 3600);
            }

            // Jeda ~120 ms → maksimal ~500/menit (batas Google 600/menit).
            usleep(120000);
        }

        Cache::put(self::PROGRESS_KEY, [
            'status' => 'done',
            'message' => "Selesai. {$done} halaman diperiksa" . ($gagal ? " ({$gagal} gagal)" : '') . '.',
            'done' => $done, 'total' => $total,
        ], 3600);
    }

    /**
     * @return array<string,mixed>
     */
    public static function progress(): array
    {
        return Cache::get(self::PROGRESS_KEY, ['status' => 'idle', 'message' => '', 'done' => 0, 'total' => 0]);
    }
}
