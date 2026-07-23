<?php

namespace App\Console\Commands;

use App\Jobs\PublishPagesJob;
use App\Support\PublishControl;
use Illuminate\Console\Command;

class PublishCommand extends Command
{
    protected $signature = 'daya:publish {--batch= : Batasi ke import_batch_id tertentu}';

    protected $description = 'Mulai publish queue (draft → published) via queue.';

    public function handle(): int
    {
        $batch = $this->option('batch') ? (int) $this->option('batch') : null;
        $targetCount = \App\Models\Page::query()
            ->draft()
            ->when($batch, fn ($query) => $query->where('import_batch_id', $batch))
            ->count();

        PublishControl::start($batch, $targetCount);
        PublishPagesJob::dispatch($batch);

        $this->info('Publish queue dimulai. Jalankan worker: php artisan queue:work');

        return self::SUCCESS;
    }
}
