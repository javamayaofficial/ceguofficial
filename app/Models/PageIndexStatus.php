<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageIndexStatus extends Model
{
    protected $fillable = [
        'page_id', 'verdict', 'coverage_state', 'robots_state', 'source',
        'last_crawl_at', 'checked_at', 'requested_at', 'error',
    ];

    protected $casts = [
        'last_crawl_at' => 'datetime',
        'checked_at' => 'datetime',
        'requested_at' => 'datetime',
    ];

    /** Kata kunci coverage_state yang berarti SUDAH terindeks. */
    public const INDEXED_HINTS = ['submitted and indexed', 'indexed, not submitted'];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /** Apakah status ini berarti halaman sudah terindeks Google? */
    public function isIndexed(): bool
    {
        $state = mb_strtolower((string) $this->coverage_state);
        foreach (self::INDEXED_HINTS as $hint) {
            if (str_contains($state, $hint)) {
                return true;
            }
        }

        return false;
    }
}
