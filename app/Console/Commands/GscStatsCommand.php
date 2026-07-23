<?php

namespace App\Console\Commands;

use App\Services\SearchConsole\SearchConsoleService;
use Illuminate\Console\Command;

/**
 * Tampilkan ringkasan performa dari Google Search Console.
 *   php artisan gsc:stats --days=28
 */
class GscStatsCommand extends Command
{
    protected $signature = 'gsc:stats {--days=28 : Rentang hari}';

    protected $description = 'Ringkasan klik/impresi dari Google Search Console.';

    public function handle(SearchConsoleService $gsc): int
    {
        if (! SearchConsoleService::isConfigured()) {
            $this->error('GSC belum dikonfigurasi. Isi GSC_CREDENTIALS & GSC_SITE_URL di .env.');

            return self::FAILURE;
        }

        $days = max(1, (int) $this->option('days'));

        try {
            $s = $gsc->summary($days);
            $this->info("Ringkasan {$days} hari terakhir:");
            $this->table(
                ['Klik', 'Impresi', 'CTR %', 'Posisi rata2'],
                [[$s['clicks'], $s['impressions'], $s['ctr'], $s['position']]],
            );

            $rows = array_map(fn ($p) => [$p['page'], $p['clicks'], $p['impressions']], $gsc->topPages($days, 10));
            if ($rows) {
                $this->info('Halaman teratas:');
                $this->table(['Halaman', 'Klik', 'Impresi'], $rows);
            }
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
