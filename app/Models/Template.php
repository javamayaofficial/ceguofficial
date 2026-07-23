<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    protected $fillable = ['name', 'content', 'css', 'js', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    /**
     * Template aktif yang dipakai semua halaman. Di-cache agar render cepat.
     */
    public static function active(): ?self
    {
        return cache()->remember('template.active', 3600, function () {
            return static::where('is_active', true)->first();
        });
    }

    public static function flushActiveCache(): void
    {
        cache()->forget('template.active');
    }

    /**
     * Pastikan hanya satu template aktif, lalu bersihkan cache.
     */
    public function makeActive(): void
    {
        static::where('id', '!=', $this->id)->update(['is_active' => false]);
        $this->update(['is_active' => true]);
        static::flushActiveCache();
    }
}
