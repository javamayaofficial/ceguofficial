<?php

namespace App\Services\SearchConsole;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Query Google Search Console (Search Analytics API) untuk ringkasan performa:
 * total klik, impresi, CTR, posisi rata-rata, dan halaman teratas.
 *
 * Semua metode melempar RuntimeException bila belum dikonfigurasi/gagal —
 * pemanggil (dashboard/command) membungkusnya agar UI tak pernah rusak.
 */
class SearchConsoleService
{
    private const API = 'https://searchconsole.googleapis.com/webmasters/v3';

    public function __construct(private readonly GoogleServiceAccountToken $token)
    {
    }

    public static function isConfigured(): bool
    {
        return trim((string) config('services.gsc.credentials', '')) !== '';
    }

    private function siteUrl(): string
    {
        $site = trim((string) config('services.gsc.site_url', ''));

        return $site !== '' ? $site : url('/');
    }

    /**
     * Ringkasan agregat untuk N hari terakhir.
     *
     * @return array{clicks:int, impressions:int, ctr:float, position:float, days:int}
     */
    public function summary(int $days = 28): array
    {
        $rows = $this->query([
            'startDate' => now()->subDays($days)->toDateString(),
            'endDate' => now()->toDateString(),
            'dimensions' => [],
            'rowLimit' => 1,
        ]);

        $r = $rows[0] ?? [];

        return [
            'clicks' => (int) round($r['clicks'] ?? 0),
            'impressions' => (int) round($r['impressions'] ?? 0),
            'ctr' => round(($r['ctr'] ?? 0) * 100, 2),
            'position' => round($r['position'] ?? 0, 1),
            'days' => $days,
        ];
    }

    /**
     * Halaman dengan performa teratas.
     *
     * @return array<int,array{page:string, clicks:int, impressions:int}>
     */
    public function topPages(int $days = 28, int $limit = 10): array
    {
        $rows = $this->query([
            'startDate' => now()->subDays($days)->toDateString(),
            'endDate' => now()->toDateString(),
            'dimensions' => ['page'],
            'rowLimit' => $limit,
        ]);

        return array_map(fn ($r) => [
            'page' => $r['keys'][0] ?? '',
            'clicks' => (int) round($r['clicks'] ?? 0),
            'impressions' => (int) round($r['impressions'] ?? 0),
        ], $rows);
    }

    /**
     * Sebaran PERINGKAT: berapa halaman yang benar-benar muncul di Google,
     * dan di posisi berapa. Halaman yang punya impresi = halaman yang tampil
     * di hasil pencarian (meski belum tentu diklik).
     *
     * @return array{total_tampil:int, top3:int, top10:int, top20:int, sisanya:int,
     *               ada_klik:int, tanpa_klik:int, halaman:array<int,array<string,mixed>>}
     */
    public function rankingBreakdown(int $days = 28, int $limit = 5000): array
    {
        $rows = $this->query([
            'startDate' => now()->subDays($days)->toDateString(),
            'endDate' => now()->toDateString(),
            'dimensions' => ['page'],
            'rowLimit' => min(25000, max(1, $limit)),
        ]);

        $out = [
            'total_tampil' => 0, 'top3' => 0, 'top10' => 0, 'top20' => 0,
            'sisanya' => 0, 'ada_klik' => 0, 'tanpa_klik' => 0, 'halaman' => [],
        ];

        foreach ($rows as $r) {
            $pos = (float) ($r['position'] ?? 0);
            $klik = (int) round($r['clicks'] ?? 0);
            $imp = (int) round($r['impressions'] ?? 0);

            if ($imp <= 0) {
                continue;
            }

            $out['total_tampil']++;
            $klik > 0 ? $out['ada_klik']++ : $out['tanpa_klik']++;

            if ($pos <= 3) {
                $out['top3']++;
            } elseif ($pos <= 10) {
                $out['top10']++;
            } elseif ($pos <= 20) {
                $out['top20']++;
            } else {
                $out['sisanya']++;
            }

            if (count($out['halaman']) < 20) {
                $out['halaman'][] = [
                    'url' => $r['keys'][0] ?? '',
                    'klik' => $klik,
                    'impresi' => $imp,
                    'posisi' => round($pos, 1),
                ];
            }
        }

        usort($out['halaman'], fn ($a, $b) => $b['impresi'] <=> $a['impresi']);

        return $out;
    }

    /**
     * ESTIMASI HALAMAN TERINDEKS TANPA KUOTA PER-URL.
     *
     * Halaman yang punya impresi di Search Analytics PASTI sudah terindeks —
     * tidak mungkin muncul di hasil pencarian bila tidak ada di indeks.
     *
     * Berbeda dengan URL Inspection API (2.000/hari), endpoint ini
     * mengembalikan hingga 25.000 baris sekali panggil dan bisa dipaginasi,
     * sehingga cocok untuk memantau ratusan ribu halaman.
     *
     * Catatan kejujuran: ini adalah BATAS BAWAH, bukan angka pasti. Halaman yang
     * terindeks tetapi belum pernah muncul di pencarian (tidak ada impresi)
     * tidak terhitung di sini. Jadi "terindeks minimal N".
     *
     * @return array{terindeks_minimal:int, halaman_diperiksa:int, hari:int,
     *               tercapai_batas:bool, urls:array<int,string>}
     */
    public function indexedByAnalytics(int $days = 90, int $maxRows = 100000): array
    {
        $start = now()->subDays($days)->toDateString();
        $end = now()->toDateString();

        $perPage = 25000;
        $startRow = 0;
        $unik = [];
        $batas = false;

        while (count($unik) < $maxRows) {
            $rows = $this->query([
                'startDate' => $start,
                'endDate' => $end,
                'dimensions' => ['page'],
                'rowLimit' => $perPage,
                'startRow' => $startRow,
            ]);

            if ($rows === []) {
                break;
            }

            foreach ($rows as $r) {
                if ((int) round($r['impressions'] ?? 0) > 0) {
                    $url = $r['keys'][0] ?? '';
                    if ($url !== '') {
                        $unik[$url] = true;
                    }
                }
            }

            if (count($rows) < $perPage) {
                break; // sudah habis
            }

            $startRow += $perPage;

            if ($startRow >= $maxRows) {
                $batas = true;
                break;
            }
        }

        return [
            'terindeks_minimal' => count($unik),
            'halaman_diperiksa' => $startRow + $perPage,
            'hari' => $days,
            'tercapai_batas' => $batas,
            'urls' => array_keys($unik),
        ];
    }

    /**
     * @param array<string,mixed> $body
     * @return array<int,array<string,mixed>>
     */
    private function query(array $body): array
    {
        if (! self::isConfigured()) {
            throw new RuntimeException('Search Console belum dikonfigurasi (GSC_CREDENTIALS).');
        }

        $endpoint = self::API . '/sites/' . rawurlencode($this->siteUrl()) . '/searchAnalytics/query';

        $resp = Http::withToken($this->token->accessToken())
            ->timeout(30)
            ->acceptJson()
            ->post($endpoint, $body);

        if ($resp->failed()) {
            throw new RuntimeException('GSC API gagal (HTTP ' . $resp->status() . '): ' . mb_substr($resp->body(), 0, 200));
        }

        return (array) $resp->json('rows', []);
    }
}
