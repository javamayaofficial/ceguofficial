<?php

namespace App\Services\SearchConsole;

use App\Models\Page;
use App\Models\PageIndexStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * URL Inspection API (Google Search Console) — memeriksa apakah sebuah URL
 * sudah terindeks Google, dan bila belum, apa alasannya.
 *
 * KUOTA GOOGLE (per properti):
 *   - 2.000 permintaan / hari
 *   - 600 permintaan / menit
 * Service ini menghitung pemakaian harian sendiri dan MENOLAK melampaui batas,
 * agar akses API situs Anda tidak diblokir Google.
 *
 * Catatan: API ini hanya MEMERIKSA, tidak meminta pengindeksan. Lihat
 * docs/INDEXING.md untuk penjelasan mengapa "request indexing" massal lewat API
 * tidak tersedia untuk halaman umum.
 */
class UrlInspectionService
{
    private const API = 'https://searchconsole.googleapis.com/v1/urlInspection/index:inspect';

    /** Batas harian resmi Google. Disisakan sedikit sebagai pengaman. */
    public const DAILY_QUOTA = 2000;

    public function __construct(private readonly GoogleServiceAccountToken $token)
    {
    }

    public static function isConfigured(): bool
    {
        return SearchConsoleService::isConfigured();
    }

    // ---------------------------------------------------------------
    // Kuota
    // ---------------------------------------------------------------

    private static function quotaKey(): string
    {
        return 'gsc:inspect:quota:' . now()->toDateString();
    }

    /** Jumlah pemanggilan hari ini. */
    public static function usedToday(): int
    {
        return (int) Cache::get(self::quotaKey(), 0);
    }

    /** Sisa kuota hari ini. */
    public static function remainingToday(): int
    {
        return max(0, self::DAILY_QUOTA - self::usedToday());
    }

    private static function consume(int $n = 1): void
    {
        $key = self::quotaKey();
        Cache::put($key, self::usedToday() + $n, now()->endOfDay()->addMinutes(5));
    }

    // ---------------------------------------------------------------
    // Inspeksi
    // ---------------------------------------------------------------

    /**
     * Periksa satu halaman & simpan hasilnya.
     *
     * @throws RuntimeException bila kuota habis atau API gagal.
     */
    public function inspect(Page $page): PageIndexStatus
    {
        if (! self::isConfigured()) {
            throw new RuntimeException('Search Console belum dikonfigurasi (GSC_CREDENTIALS).');
        }

        if (self::remainingToday() <= 0) {
            throw new RuntimeException('Kuota inspeksi harian Google (2.000) sudah habis. Coba lagi besok.');
        }

        $url = url('/' . ltrim((string) $page->path, '/'));
        $site = trim((string) config('services.gsc.site_url', '')) ?: url('/');

        try {
            $resp = Http::withToken($this->token->accessToken())
                ->timeout(60)
                ->acceptJson()
                ->post(self::API, [
                    'inspectionUrl' => $url,
                    'siteUrl' => $site,
                    'languageCode' => 'id',
                ]);
        } catch (\Throwable $e) {
            throw new RuntimeException('Gagal menghubungi Google: ' . $e->getMessage());
        }

        self::consume();

        if ($resp->failed()) {
            $msg = (string) data_get($resp->json(), 'error.message', $resp->body());

            // Simpan error agar terlihat di panel, jangan diam-diam gagal.
            return $this->store($page, ['error' => mb_substr($msg, 0, 500)]);
        }

        $r = (array) data_get($resp->json(), 'inspectionResult.indexStatusResult', []);

        return $this->store($page, [
            'verdict' => (string) ($r['verdict'] ?? ''),
            'coverage_state' => (string) ($r['coverageState'] ?? ''),
            'robots_state' => (string) ($r['robotsTxtState'] ?? ''),
            'last_crawl_at' => ! empty($r['lastCrawlTime']) ? \Illuminate\Support\Carbon::parse($r['lastCrawlTime']) : null,
            'error' => null,
        ]);
    }

    /**
     * @param array<string,mixed> $data
     */
    private function store(Page $page, array $data): PageIndexStatus
    {
        return PageIndexStatus::updateOrCreate(
            ['page_id' => $page->id],
            $data + ['checked_at' => now()],
        );
    }

    /**
     * Ringkasan status indexing dari data yang SUDAH diperiksa.
     *
     * @return array<string,mixed>
     */
    public static function summary(): array
    {
        $published = (int) Page::published()->count();
        $checked = (int) PageIndexStatus::count();

        $indexed = 0;
        $notIndexed = 0;
        $alasan = [];

        foreach (PageIndexStatus::query()->select('coverage_state')->get() as $row) {
            $state = trim((string) $row->coverage_state);
            if ($state === '') {
                continue;
            }

            $lower = mb_strtolower($state);
            $isIndexed = false;
            foreach (PageIndexStatus::INDEXED_HINTS as $hint) {
                if (str_contains($lower, $hint)) {
                    $isIndexed = true;
                    break;
                }
            }

            if ($isIndexed) {
                $indexed++;
            } else {
                $notIndexed++;
                $alasan[$state] = ($alasan[$state] ?? 0) + 1;
            }
        }

        arsort($alasan);

        return [
            'published' => $published,
            'checked' => $checked,
            'belum_dicek' => max(0, $published - $checked),
            'terindeks' => $indexed,
            'belum_terindeks' => $notIndexed,
            'alasan' => array_slice($alasan, 0, 8, true),
            'kuota_terpakai' => self::usedToday(),
            'kuota_sisa' => self::remainingToday(),
        ];
    }
}
