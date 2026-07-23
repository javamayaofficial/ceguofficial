<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Services\IndexNowService;
use Illuminate\Console\Command;

/**
 * Kirim URL halaman published ke IndexNow secara manual.
 *
 * Contoh:
 *   php artisan indexnow:submit                 # 10.000 URL terbaru
 *   php artisan indexnow:submit --limit=50000   # 50.000 URL terbaru
 *   php artisan indexnow:submit --all           # semua (hati-hati kuota harian)
 */
class IndexNowSubmitCommand extends Command
{
    protected $signature = 'indexnow:submit {--limit=10000 : Jumlah URL terbaru} {--all : Kirim SEMUA halaman published}';

    protected $description = 'Kirim URL halaman published ke IndexNow (indexing kilat).';

    public function handle(IndexNowService $indexNow): int
    {
        if (! IndexNowService::isEnabled()) {
            $this->error('IndexNow nonaktif. Isi INDEXNOW_KEY di .env lalu jalankan: php artisan config:clear');

            return self::FAILURE;
        }

        $all = (bool) $this->option('all');
        $limit = max(1, (int) $this->option('limit'));

        $total = 0;

        if ($all) {
            $this->warn('Mengirim SEMUA halaman published. Perhatikan kuota harian IndexNow.');
            $lastId = 0;
            do {
                $pages = Page::published()
                    ->where('id', '>', $lastId)
                    ->orderBy('id')
                    ->limit(10000)
                    ->get(['id', 'path']);

                if ($pages->isEmpty()) {
                    break;
                }

                $urls = $pages->map(fn ($p) => url('/' . ltrim((string) $p->path, '/')))->all();
                $total += $indexNow->submit($urls);
                $lastId = (int) $pages->last()->id;
                $this->line("Terkirim: {$total} URL…");
            } while ($pages->count() === 10000);
        } else {
            $urls = Page::published()
                ->orderByDesc('id')
                ->limit($limit)
                ->pluck('path')
                ->map(fn ($p) => url('/' . ltrim((string) $p, '/')))
                ->all();
            $total = $indexNow->submit($urls);
        }

        $this->info("Selesai. {$total} URL dikirim ke IndexNow.");

        return self::SUCCESS;
    }
}
