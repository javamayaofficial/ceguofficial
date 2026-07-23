<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * IndexNow — protokol ping agar mesin pencari (Bing, Yandex, Naver, Seznam,
 * dan makin banyak lainnya) langsung tahu ada URL baru/berubah, tanpa menunggu
 * crawl rutin. Sangat berguna untuk pSEO skala besar: begitu halaman dipublish,
 * langsung diberitahukan.
 *
 * Aktif hanya bila INDEXNOW_KEY diisi. Semua kegagalan ditelan (log saja) —
 * proses publish TIDAK BOLEH gagal gara-gara IndexNow.
 *
 * Verifikasi kepemilikan: file /indexnow.txt (route) menyajikan key. Kita kirim
 * keyLocation absolut ke endpoint agar mesin pencari bisa mem-verifikasi.
 */
class IndexNowService
{
    /** Batas aman URL per request (spesifikasi IndexNow: maks 10.000). */
    private const BATCH = 10000;

    public static function isEnabled(): bool
    {
        return trim((string) config('services.indexnow.key', '')) !== '';
    }

    public static function key(): string
    {
        return trim((string) config('services.indexnow.key', ''));
    }

    /**
     * Kirim daftar URL absolut ke IndexNow. Dipecah per BATCH.
     *
     * @param array<int,string> $urls
     * @return int Jumlah URL yang terkirim (0 bila nonaktif/gagal).
     */
    public function submit(array $urls): int
    {
        if (! self::isEnabled()) {
            return 0;
        }

        $urls = array_values(array_unique(array_filter(array_map('trim', $urls))));
        if (empty($urls)) {
            return 0;
        }

        $host = parse_url(url('/'), PHP_URL_HOST) ?: '';
        $key = self::key();
        $keyLocation = url('/indexnow.txt');
        $endpoint = (string) config('services.indexnow.endpoint', 'https://api.indexnow.org/indexnow');

        $sent = 0;
        foreach (array_chunk($urls, self::BATCH) as $chunk) {
            try {
                $resp = Http::timeout(30)->acceptJson()->post($endpoint, [
                    'host' => $host,
                    'key' => $key,
                    'keyLocation' => $keyLocation,
                    'urlList' => array_values($chunk),
                ]);

                if ($resp->successful() || $resp->status() === 202) {
                    $sent += count($chunk);
                } else {
                    Log::warning('IndexNow menolak batch', [
                        'status' => $resp->status(),
                        'body' => mb_substr($resp->body(), 0, 200),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('IndexNow gagal: ' . $e->getMessage());
            }
        }

        return $sent;
    }
}
