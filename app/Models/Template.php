<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    public const TYPE_SALESPAGE = 'salespage';
    public const TYPE_HOME = 'home';

    public const TYPES = [
        self::TYPE_SALESPAGE => 'Salespage (halaman per wilayah)',
        self::TYPE_HOME => 'Beranda',
    ];

    protected $fillable = ['name', 'type', 'content', 'css', 'js', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    /**
     * Template aktif yang dipakai semua halaman. Di-cache agar render cepat.
     */
    public static function active(string $type = self::TYPE_SALESPAGE): ?self
    {
        return cache()->remember("template.active.{$type}", 3600, function () use ($type) {
            return static::where('type', $type)->where('is_active', true)->first();
        });
    }

    public static function flushActiveCache(): void
    {
        foreach (array_keys(self::TYPES) as $type) {
            cache()->forget("template.active.{$type}");
        }
        cache()->forget('template.active');
    }

    /**
     * Pastikan hanya satu template aktif, lalu bersihkan cache.
     */
    public function makeActive(): void
    {
        static::where('id', '!=', $this->id)
            ->where('type', $this->type ?: self::TYPE_SALESPAGE)
            ->update(['is_active' => false]);
        $this->update(['is_active' => true]);
        static::flushActiveCache();
    }
}
