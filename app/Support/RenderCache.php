<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Cache HTML halaman publik (versioned).
 *
 * Karena render halaman deterministik (template + pool variasi + seed),
 * HTML hasil render aman di-cache. Kunci cache membawa nomor VERSI global —
 * saat admin mengubah template/konten/FAQ/pengaturan, cukup panggil bump():
 * versi naik, seluruh cache lama otomatis tidak terpakai (kadaluarsa via TTL),
 * tanpa perlu menghapus jutaan key satu per satu.
 *
 * CATATAN KAPASITAS: jangan pernah cache 2 juta halaman sekaligus.
 * TTL sengaja pendek (default 900 detik, atur via CEGU_PAGE_CACHE_TTL)
 * sehingga hanya halaman yang SEDANG ramai diakses/di-crawl yang tersimpan.
 * Untuk produksi skala besar, gunakan Redis dengan maxmemory + allkeys-lru
 * (lihat docs/PANDUAN-UPGRADE.md).
 */
class RenderCache
{
    private const VERSION_KEY = 'daya:render:version';

    public static function version(): int
    {
        return (int) Cache::rememberForever(self::VERSION_KEY, fn () => 1);
    }

    /**
     * Naikkan versi → semua cache halaman lama efektif hangus.
     */
    public static function bump(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }

    public static function key(string $path): string
    {
        return 'page:v' . self::version() . ':' . md5($path);
    }

    public static function ttl(): int
    {
        return max(0, (int) config('daya.page_cache_ttl', 900));
    }
}
