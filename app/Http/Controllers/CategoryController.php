<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\District;
use App\Models\Page;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Village;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

/**
 * Halaman hub/kategori untuk path 1–3 segmen. Menampilkan daftar tautan ke level
 * di bawahnya (kota → kecamatan → kelurahan) untuk memperkuat internal linking
 * & crawling. Hanya menghitung halaman ber-status published.
 *
 * PATCH SKALA: deduplikasi dilakukan di LEVEL SQL (subquery DISTINCT terhadap
 * indeks komposit `pages`), bukan dengan menarik ratusan ribu baris ke memori
 * PHP. Hasilnya juga di-cache singkat (CEGU_CATEGORY_CACHE_TTL).
 */
class CategoryController extends Controller
{
    /**
     * @param array<int,string> $segments
     */
    public function show(array $segments)
    {
        [$serviceSlug, $citySlug, $districtSlug] = array_pad($segments, 3, null);

        $service = Service::where('slug', $serviceSlug)->first();
        if (! $service) {
            abort(404);
        }

        if ($citySlug === null) {
            return $this->cities($service);
        }

        $city = City::where('slug', $citySlug)->first();
        if (! $city) {
            abort(404);
        }

        if ($districtSlug === null) {
            return $this->districts($service, $city);
        }

        $district = District::where('city_id', $city->id)->where('slug', $districtSlug)->first();
        if (! $district) {
            abort(404);
        }

        return $this->villages($service, $city, $district);
    }

    private function cities(Service $service)
    {
        $items = $this->rememberCategory("cat.cities.{$service->id}", function () use ($service) {
            return City::query()
                ->whereIn('id', Page::published()
                    ->where('service_id', $service->id)
                    ->select('city_id')
                    ->distinct())
                ->orderBy('name')
                ->get(['name', 'slug'])
                ->map(fn (City $c) => [
                    'label' => $c->name,
                    'url' => url("/{$service->slug}/{$c->slug}"),
                ])->all();
        });

        return $this->view($service->name, [
            ['label' => 'Beranda', 'url' => url('/')],
            ['label' => $service->name, 'url' => url("/{$service->slug}")],
        ], 'Pilih kota untuk layanan ' . $service->name, $items);
    }

    private function districts(Service $service, City $city)
    {
        $items = $this->rememberCategory("cat.districts.{$service->id}.{$city->id}", function () use ($service, $city) {
            return District::query()
                ->whereIn('id', Page::published()
                    ->where('service_id', $service->id)
                    ->where('city_id', $city->id)
                    ->select('district_id')
                    ->distinct())
                ->orderBy('name')
                ->get(['name', 'slug'])
                ->map(fn (District $d) => [
                    'label' => $d->name,
                    'url' => url("/{$service->slug}/{$city->slug}/{$d->slug}"),
                ])->all();
        });

        return $this->view("{$service->name} di {$city->name}", [
            ['label' => 'Beranda', 'url' => url('/')],
            ['label' => $service->name, 'url' => url("/{$service->slug}")],
            ['label' => $city->name, 'url' => url("/{$service->slug}/{$city->slug}")],
        ], "Pilih kecamatan di {$city->name}", $items);
    }

    private function villages(Service $service, City $city, District $district)
    {
        $items = $this->rememberCategory("cat.villages.{$service->id}.{$district->id}", function () use ($service, $district) {
            // Kelurahan per kecamatan jumlahnya kecil (belasan), aman diambil
            // beserta path halaman (unique combo menjamin 1 halaman/kelurahan).
            return Page::published()
                ->where('service_id', $service->id)
                ->where('district_id', $district->id)
                ->with('village:id,name')
                ->get(['village_id', 'path'])
                ->map(fn (Page $p) => [
                    'label' => trim("{$service->name} " . ($p->village?->name ?? '')),
                    'url' => url('/' . $p->path),
                ])
                ->sortBy('label')->values()->all();
        });

        return $this->view("{$service->name} di {$district->name}", [
            ['label' => 'Beranda', 'url' => url('/')],
            ['label' => $service->name, 'url' => url("/{$service->slug}")],
            ['label' => $city->name, 'url' => url("/{$service->slug}/{$city->slug}")],
            ['label' => $district->name, 'url' => url("/{$service->slug}/{$city->slug}/{$district->slug}")],
        ], "Pilih kelurahan di {$district->name}", $items);
    }

    /**
     * @return array<int, array{label:string,url:string}>
     */
    private function rememberCategory(string $key, \Closure $resolver): array
    {
        $ttl = max(0, (int) config('daya.category_cache_ttl', 600));

        return $ttl > 0 ? cache()->remember($key, $ttl, $resolver) : $resolver();
    }

    private function view(string $title, array $breadcrumb, string $heading, array $items)
    {
        if (empty($items)) {
            abort(404);
        }

        $seo = $this->buildSeo($title, $heading, $breadcrumb, count($items));

        return View::make('category', compact('title', 'breadcrumb', 'heading', 'items', 'seo'));
    }

    /**
     * Data SEO untuk halaman hub. Sebelumnya controller hanya mengirim $title
     * (dipakai H1), sehingga layout jatuh ke judul default brand — akibatnya
     * SEMUA hub punya <title> identik dan berisiko dinilai duplikat oleh Google.
     *
     * @param array<int,array{label:string,url:string}> $breadcrumb
     * @return array<string,mixed>
     */
    private function buildSeo(string $title, string $heading, array $breadcrumb, int $count): array
    {
        $brand = (string) Setting::get('brand_name', '');
        $last = $breadcrumb[count($breadcrumb) - 1] ?? null;
        $canonical = $last['url'] ?? url()->current();

        // Deskripsi unik per hub: heading + jumlah item + ajakan.
        $description = Str::limit(
            trim("{$heading}. Tersedia {$count} pilihan. Konsultasi gratis via WhatsApp bersama {$brand}."),
            155,
            '…',
        );

        return [
            'title' => Str::limit($title, 60, '') . " | {$brand}",
            'description' => $description,
            'canonical' => $canonical,
            'h1' => $title,
            'breadcrumb' => $breadcrumb,
            'schema' => $this->schema($title, $description, $canonical, $breadcrumb),
            'type' => 'website',
            'robots' => (string) Setting::get('default_robots', 'index,follow'),
        ];
    }

    /**
     * JSON-LD: BreadcrumbList + CollectionPage (halaman hub adalah daftar).
     *
     * @param array<int,array{label:string,url:string}> $breadcrumb
     */
    private function schema(string $title, string $description, string $canonical, array $breadcrumb): string
    {
        $items = [];
        foreach ($breadcrumb as $i => $crumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $crumb['label'],
                'item' => $crumb['url'],
            ];
        }

        $payload = [
            '@context' => 'https://schema.org',
            '@graph' => [
                ['@type' => 'BreadcrumbList', 'itemListElement' => $items],
                [
                    '@type' => 'CollectionPage',
                    'name' => $title,
                    'description' => $description,
                    'url' => $canonical,
                ],
            ],
        ];

        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
