<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Kontrol antrian publish (Start/Pause/Resume/Status) via cache flag.
 * Dipisah dari proses generate sesuai RFP (Publish Queue terpisah).
 */
class PublishControl
{
    private const KEY = 'daya:publish:state';
    private const META_KEY = 'daya:publish:meta';

    public const RUNNING = 'running';
    public const PAUSED = 'paused';
    public const IDLE = 'idle';

    public static function state(): string
    {
        return Cache::get(self::KEY, self::IDLE);
    }

    public static function setState(string $state): void
    {
        Cache::forever(self::KEY, $state);
    }

    public static function start(?int $batchId, int $targetCount): array
    {
        $meta = [
            'run_id' => (string) Str::uuid(),
            'batch_id' => $batchId,
            'target_count' => max(0, $targetCount),
            'started_at' => now()->toIso8601String(),
            'completed_at' => null,
            'completed_count' => 0,
            'message' => null,
        ];

        self::setState(self::RUNNING);
        Cache::forever(self::META_KEY, $meta);

        return $meta;
    }

    public static function pause(): void
    {
        self::setState(self::PAUSED);
    }

    public static function resume(): void
    {
        self::setState(self::RUNNING);
    }

    public static function finish(?int $completedCount = null): array
    {
        $meta = self::meta();
        $done = $completedCount ?? (int) ($meta['target_count'] ?? 0);
        $meta['completed_at'] = now()->toIso8601String();
        $meta['completed_count'] = max(0, $done);
        $meta['message'] = $done > 0
            ? 'Publish selesai. ' . number_format($done) . ' halaman berhasil dipublikasikan.'
            : 'Publish selesai. Tidak ada draft tersisa untuk dipublikasikan.';

        self::setState(self::IDLE);
        Cache::forever(self::META_KEY, $meta);

        return $meta;
    }

    public static function meta(): array
    {
        return array_merge([
            'run_id' => null,
            'batch_id' => null,
            'target_count' => 0,
            'started_at' => null,
            'completed_at' => null,
            'completed_count' => 0,
            'message' => null,
        ], Cache::get(self::META_KEY, []));
    }

    public static function batchId(): ?int
    {
        $batchId = self::meta()['batch_id'] ?? null;

        return $batchId !== null ? (int) $batchId : null;
    }

    public static function isPaused(): bool
    {
        return self::state() === self::PAUSED;
    }
}
