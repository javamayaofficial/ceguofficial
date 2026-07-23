<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Services\SitemapService;
use Illuminate\Console\Command;

class StatsCommand extends Command
{
    protected $signature = 'daya:stats';

    protected $description = 'Tampilkan ringkasan jumlah halaman & sitemap.';

    public function handle(SitemapService $sitemap): int
    {
        $this->table(['Metrik', 'Nilai'], [
            ['Total halaman', number_format(Page::count())],
            ['Draft', number_format(Page::draft()->count())],
            ['Published', number_format(Page::published()->count())],
            ['Jumlah sitemap file', number_format($sitemap->chunkCount())],
        ]);

        return self::SUCCESS;
    }
}
