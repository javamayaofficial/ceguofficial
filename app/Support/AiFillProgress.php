<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Status proses "Isi Otomatis dengan AI" — disimpan di cache agar panel admin
 * bisa menampilkan progres tanpa tabel baru. Job menulis, controller/view membaca.
 */
class AiFillProgress
{
    private const KEY = 'daya:ai:fill';
    private const TTL = 3600;

    public const RUNNING = 'running';
    public const DONE = 'done';
    public const ERROR = 'error';

    /**
     * @return array<string,mixed>
     */
    public static function get(): array
    {
        return Cache::get(self::KEY, [
            'status' => 'idle',
            'message' => '',
            'current' => null,
            'sections' => [],
            'faq' => ['added' => 0, 'target' => 0],
            'tokens' => 0,
            'calls' => 0,
            'started_at' => null,
            'finished_at' => null,
        ]);
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function put(array $data): void
    {
        Cache::put(self::KEY, $data, self::TTL);
    }

    public static function isRunning(): bool
    {
        return (self::get()['status'] ?? '') === self::RUNNING;
    }
}
