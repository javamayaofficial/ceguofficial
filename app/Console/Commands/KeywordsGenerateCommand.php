<?php

namespace App\Console\Commands;

use App\Services\Ai\AiClientFactory;
use App\Services\Ai\KeywordGenerator;
use Illuminate\Console\Command;

/**
 * Hasilkan keyword longtail ke file (untuk otomasi/batch besar).
 *   php artisan keywords:generate "les privat" --count=200 --seeds="matematika,fisika" --out=keywords.csv
 */
class KeywordsGenerateCommand extends Command
{
    protected $signature = 'keywords:generate {business : Niche/usaha}
        {--count=100 : Jumlah keyword (10-400)}
        {--seeds= : Kata kunci awal, pisah koma}
        {--out= : Path file CSV output (opsional)}';

    protected $description = 'Generate keyword longtail (dimensi layanan) via AI.';

    public function handle(KeywordGenerator $generator): int
    {
        if (! AiClientFactory::isConfigured()) {
            $this->error('AI belum dikonfigurasi. Isi AI_* di .env lalu php artisan config:clear.');

            return self::FAILURE;
        }

        try {
            $keywords = $generator->generate([
                'business' => (string) $this->argument('business'),
                'seeds' => (string) $this->option('seeds'),
            ], (int) $this->option('count'));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(count($keywords) . ' keyword dihasilkan.');

        if ($out = $this->option('out')) {
            $csv = "layanan\n" . implode("\n", array_map(fn ($k) => '"' . str_replace('"', '""', $k) . '"', $keywords));
            file_put_contents($out, $csv);
            $this->info("Disimpan ke: {$out}");
        } else {
            foreach ($keywords as $k) {
                $this->line($k);
            }
        }

        return self::SUCCESS;
    }
}
