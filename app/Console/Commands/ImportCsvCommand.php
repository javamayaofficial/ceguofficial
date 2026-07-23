<?php

namespace App\Console\Commands;

use App\Jobs\ImportCsvJob;
use App\Models\ImportBatch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportCsvCommand extends Command
{
    protected $signature = 'daya:import {file : Path ke file CSV (layanan,kota,kecamatan,kelurahan)}';

    protected $description = 'Import CSV dari CLI lalu generate halaman (via queue).';

    public function handle(): int
    {
        $file = $this->argument('file');
        if (! is_file($file)) {
            $this->error("File tidak ditemukan: {$file}");

            return self::FAILURE;
        }

        $stored = 'imports/cli-' . now()->format('Ymd-His') . '-' . basename($file);
        Storage::disk('local')->put($stored, file_get_contents($file));

        $batch = ImportBatch::create([
            'original_filename' => basename($file),
            'stored_path' => $stored,
            'status' => ImportBatch::STATUS_QUEUED,
        ]);

        ImportCsvJob::dispatch($batch->id);

        $this->info("Batch #{$batch->id} dibuat. Jalankan worker untuk memproses: php artisan queue:work");

        return self::SUCCESS;
    }
}
