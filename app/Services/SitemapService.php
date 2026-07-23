<?php

namespace App\Services;

use App\Models\Page;

/**
 * Sitemap otomatis (RFP): maksimal 50.000 URL per sitemap; bila melebihi,
 * sistem otomatis membuat sitemap index. /sitemap.xml selalu berupa index yang
 * menunjuk ke /sitemap-{n}.xml.
 *
 * PATCH SKALA: pagination OFFSET diganti KEYSET. Pada 2 juta baris, chunk
 * terakhir dengan OFFSET 1.950.000 memaksa DB memindai hampir seluruh indeks.
 * Kini batas (id awal–akhir) tiap chunk dihitung sekali dan di-cache; setiap
 * chunk lalu diambil dengan WHERE id BETWEEN ... (memakai indeks (status,id)).
 */
class SitemapService
{
    public const PER_FILE = 50000;

    private const CACHE_TTL = 300; // 5 menit

    public function publishedCount(): int
    {
        return cache()->remember('sitemap.count', self::CACHE_TTL, fn () => Page::published()->count());
    }

    public function chunkCount(): int
    {
        return max(1, count($this->boundaries()));
    }

    /**
     * Batas id per chunk: [['from'=>..,'to'=>..], ...] — dihitung via keyset
     * (index-only scan), bukan offset.
     *
     * @return array<int, array{from:int, to:int}>
     */
    private function boundaries(): array
    {
        return cache()->remember('sitemap.boundaries', self::CACHE_TTL, function () {
            $bounds = [];
            $last = 0;

            do {
                $ids = Page::published()
                    ->where('id', '>', $last)
                    ->orderBy('id')
                    ->limit(self::PER_FILE)
                    ->pluck('id');

                if ($ids->isEmpty()) {
                    break;
                }

                $bounds[] = ['from' => (int) $ids->first(), 'to' => (int) $ids->last()];
                $last = (int) $ids->last();
            } while ($ids->count() === self::PER_FILE);

            return $bounds;
        });
    }

    /**
     * XML sitemap index → daftar file sitemap-{n}.xml.
     */
    public function indexXml(): string
    {
        $chunks = $this->chunkCount();
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        for ($i = 1; $i <= $chunks; $i++) {
            $xml .= '  <sitemap><loc>' . e(url("/sitemap-{$i}.xml")) . "</loc></sitemap>\n";
        }

        // Halaman statis (Tentang, Layanan, Kontak) + beranda ikut didaftarkan,
        // agar Google menemukannya seperti salespage.
        $xml .= '  <sitemap><loc>' . e(url('/sitemap-statis.xml')) . "</loc></sitemap>\n";

        $xml .= '</sitemapindex>';

        return $xml;
    }

    /**
     * XML untuk BERANDA + halaman statis (Tentang, Layanan, Kontak, dll).
     *
     * Sebelumnya sitemap hanya memuat salespage, sehingga halaman statis tidak
     * pernah ditemukan Google. Halaman ini justru penting untuk sinyal
     * kredibilitas (E-E-A-T), jadi wajib ikut.
     */
    public function staticXml(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Beranda — prioritas tertinggi.
        $xml .= '  <url><loc>' . e(url('/')) . '</loc><priority>1.0</priority></url>' . "\n";

        if (\Illuminate\Support\Facades\Schema::hasTable('site_pages')) {
            $pages = \App\Models\SitePage::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(['slug', 'updated_at']);

            foreach ($pages as $p) {
                $xml .= '  <url><loc>' . e(url('/' . $p->slug)) . '</loc>';
                if ($p->updated_at) {
                    $xml .= '<lastmod>' . $p->updated_at->toAtomString() . '</lastmod>';
                }
                $xml .= '<priority>0.8</priority></url>' . "\n";
            }
        }

        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * XML urlset untuk satu chunk (maks 50.000 URL).
     */
    public function chunkXml(int $chunk): string
    {
        $bounds = $this->boundaries();
        $b = $bounds[$chunk - 1] ?? null;

        return cache()->remember("sitemap.chunk.{$chunk}", self::CACHE_TTL, function () use ($b) {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

            if ($b !== null) {
                Page::published()
                    ->whereBetween('id', [$b['from'], $b['to']])
                    ->orderBy('id')
                    ->get(['path', 'published_at', 'updated_at'])
                    ->each(function (Page $p) use (&$xml) {
                        $lastmod = ($p->published_at ?? $p->updated_at)?->toAtomString();
                        $xml .= '  <url><loc>' . e(url('/' . $p->path)) . '</loc>';
                        if ($lastmod) {
                            $xml .= '<lastmod>' . $lastmod . '</lastmod>';
                        }
                        $xml .= "</url>\n";
                    });
            }

            $xml .= '</urlset>';

            return $xml;
        });
    }

    public static function flushCache(): void
    {
        cache()->forget('sitemap.count');
        cache()->forget('sitemap.boundaries');
        // chunk cache kedaluwarsa otomatis (TTL 5 menit).
    }
}
