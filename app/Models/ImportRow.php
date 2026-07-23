<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportRow extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'import_batch_id', 'layanan', 'kota', 'kecamatan', 'kelurahan', 'extra', 'status', 'error',
    ];

    protected $casts = ['extra' => 'array'];

    public const STATUS_PENDING = 'pending';
    public const STATUS_DONE = 'done';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_FAILED = 'failed';
}
