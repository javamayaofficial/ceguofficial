<?php

namespace App\Jobs;

use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Services\PageGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Generate halaman dari baris staging (import_rows) secara berchunk.
 *
 * Pola "self re-dispatch per chunk" memenuhi syarat RFP:
 *  - Tidak timeout (tiap job hanya memproses 1 chunk).
 *  - Pause/Resume (cek status batch tiap chunk).
 *  - Status/progress (counter di import_batches).
 */
class GeneratePagesJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(
        public int $batchId,
        public int $chunkSize = 500,
    ) {
    }

    public function handle(PageGenerator $generator): void
    {
        $batch = ImportBatch::find($this->batchId);
        if (! $batch) {
            return;
        }

        // Pause: hentikan rantai dengan aman.
        if ($batch->isPaused()) {
            return;
        }

        $batch->update(['status' => ImportBatch::STATUS_PROCESSING]);

        $rows = ImportRow::where('import_batch_id', $batch->id)
            ->where('status', ImportRow::STATUS_PENDING)
            ->limit($this->chunkSize)
            ->get();

        if ($rows->isEmpty()) {
            $batch->update(['status' => ImportBatch::STATUS_COMPLETED]);

            return;
        }

        $generated = 0;
        $failed = 0;
        $processed = 0;

        foreach ($rows as $row) {
            try {
                $result = $generator->generate([
                    'layanan' => $row->layanan,
                    'kota' => $row->kota,
                    'kecamatan' => $row->kecamatan,
                    'kelurahan' => $row->kelurahan,
                    'extra' => $row->extra,
                ], $batch->id);

                $row->status = $result['created'] ? ImportRow::STATUS_DONE : ImportRow::STATUS_SKIPPED;
                $row->save();

                if ($result['created']) {
                    $generated++;
                }
            } catch (\Throwable $e) {
                $row->status = ImportRow::STATUS_FAILED;
                $row->error = mb_substr($e->getMessage(), 0, 250);
                $row->save();
                $failed++;
            }
            $processed++;
        }

        // Update counter batch secara atomik.
        $batch->increment('generated_count', $generated);
        $batch->increment('failed_count', $failed);
        $batch->increment('processed_rows', $processed);

        $batch->refresh();

        // Lanjut chunk berikutnya bila masih ada & tidak di-pause.
        $remaining = ImportRow::where('import_batch_id', $batch->id)
            ->where('status', ImportRow::STATUS_PENDING)
            ->exists();

        if ($remaining && ! $batch->isPaused()) {
            self::dispatch($batch->id, $this->chunkSize);
        } elseif (! $remaining) {
            $batch->update(['status' => ImportBatch::STATUS_COMPLETED]);
        }
    }
}
