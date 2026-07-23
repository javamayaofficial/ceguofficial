<?php

namespace App\Jobs;

use App\Models\Page;
use App\Services\IndexNowService;
use App\Services\SitemapService;
use App\Support\PublishControl;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Publish queue (RFP): memindahkan halaman draft → published secara berchunk,
 * terpisah dari generate, sehingga kecepatan publikasi dapat dikontrol
 * (Start/Pause/Resume) dan halaman published masuk sitemap.
 */
class PublishPagesJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(
        public ?int $importBatchId = null,
        public int $chunkSize = 1000,
    ) {
    }

    public function handle(): void
    {
        if (PublishControl::isPaused()) {
            return;
        }

        PublishControl::setState(PublishControl::RUNNING);

        $query = Page::query()->draft();
        if ($this->importBatchId) {
            $query->where('import_batch_id', $this->importBatchId);
        }

        $ids = $query->orderBy('id')->limit($this->chunkSize)->pluck('id');

        if ($ids->isEmpty()) {
            PublishControl::finish();
            SitemapService::flushCache();

            return;
        }

        Page::whereIn('id', $ids)->update([
            'status' => Page::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        // Beritahu mesin pencari (IndexNow) tentang URL yang baru dipublish agar
        // cepat terindeks. Aman: no-op bila INDEXNOW_KEY kosong, dan kegagalan
        // tidak menghentikan proses publish.
        if (IndexNowService::isEnabled()) {
            $urls = Page::whereIn('id', $ids)
                ->pluck('path')
                ->map(fn ($p) => url('/' . ltrim((string) $p, '/')))
                ->all();
            app(IndexNowService::class)->submit($urls);
        }

        // Lanjut chunk berikutnya bila tidak di-pause.
        if (! PublishControl::isPaused()) {
            self::dispatch($this->importBatchId, $this->chunkSize);
        }
    }
}
