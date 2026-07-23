<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentBlock extends Model
{
    protected $fillable = ['section', 'content', 'weight', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'weight' => 'integer',
    ];

    public const SECTIONS = [
        'hero', 'intro', 'pain_point', 'solusi', 'usp', 'testimoni', 'cta', 'about',
        // Pool kalimat AI Summary (dipindah dari kode → bisa diedit untuk niche apa pun)
        'summary_open', 'summary_bridge', 'summary_close', 'summary_filler',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSection($query, string $section)
    {
        return $query->where('section', $section);
    }
}
