<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public $timestamps = true;

    /**
     * Ambil semua setting sebagai array key=>value (di-cache).
     * Sengaja tidak memakai nama all() agar tidak menimpa Model::all().
     */
    public static function map(): array
    {
        return cache()->remember('settings.all', 3600, function () {
            return static::query()->pluck('value', 'key')->toArray();
        });
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::map()[$key] ?? $default;
    }

    public static function put(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        cache()->forget('settings.all');
    }

    public static function flushCache(): void
    {
        cache()->forget('settings.all');
    }
}
