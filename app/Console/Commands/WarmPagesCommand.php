<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Services\PageRenderer;
use App\Support\RenderCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\View;

/**
 * Panaskan cache HTML halaman publik agar kunjungan/crawl pertama sudah cepat.
 * Berguna setelah publish gelombang besar atau setelah RenderCache::bump().
 *
 *   php artisan pages:warm --limit=1000
 *   php artisan pages:warm --limit=5000 --order=random
 */
class WarmPagesCommand extends Command
{
    protected $signature = 'pages:warm {--limit=1000 : Jumlah halaman} {--order=recent : recent|random}';

    protected $description = 'Pra-render halaman published ke cache (warming).';

    public function handle(PageRenderer $renderer): int
    {
        $ttl = RenderCache::ttl();
        if ($ttl <= 0) {
            $this->warn('Cache halaman nonaktif (CEGU_PAGE_CACHE_TTL=0). Tidak ada yang dipanaskan.');

            return self::SUCCESS;
        }

        $limit = max(1, (int) $this->option('limit'));
        $query = Page::published()->with(['service', 'city', 'district', 'village']);
        $query = $this->option('order') === 'random'
            ? $query->inRandomOrder()
            : $query->orderByDesc('published_at');

        $warmed = 0;
        $skipped = 0;
        $bar = $this->output->createProgressBar($limit);

        $query->limit($limit)->get()->each(function (Page $page) use ($renderer, $ttl, &$warmed, &$skipped, $bar) {
            $key = RenderCache::key($page->path);
            if (cache()->has($key)) {
                $skipped++;
            } else {
                try {
                    $html = View::make('salespage', $renderer->render($page))->render();
                    cache()->put($key, $html, $ttl);
                    $warmed++;
                } catch (\Throwable $e) {
                    $this->newLine();
                    $this->warn("Gagal render {$page->path}: " . $e->getMessage());
                }
            }
            $bar->advance();
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Selesai. Dipanaskan: {$warmed}, sudah tercache: {$skipped}.");

        return self::SUCCESS;
    }
}
