<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    protected $fillable = [
        'original_filename', 'stored_path', 'layanan_list', 'status',
        'total_rows', 'processed_rows', 'generated_count', 'failed_count', 'error_log',
    ];

    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function isPaused(): bool
    {
        return $this->status === self::STATUS_PAUSED;
    }

    public function appendError(string $message): void
    {
        $this->error_log = trim(($this->error_log ?? '') . "\n" . now()->toDateTimeString() . ' ' . $message);
        $this->save();
    }
}
