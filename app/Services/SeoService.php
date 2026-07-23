<?php

namespace App\Services;

use App\Models\Page;
use App\Models\Setting;
use Illuminate\Support\Str;

/**
 * SEO otomatis (RFP): Meta Title, Meta Description, Canonical, H1, Breadcrumb,
 * dan Schema JSON-LD (FAQ, Breadcrumb, Organization).
 */
class SeoService
{
    public function __construct(
        private readonly UniquenessService $uniqueness,
        private readonly RegionGeo $regionGeo,
    ) {
    }

    /**
     * @param array<string,string> $tokens
     * @param array<int,array{question:string,answer:string}> $faqs
     * @return array{
     *   title:string, description:string, canonical:string, h1:string,
     *   breadcrumb: array<int,array{label:string,url:string}>,
     *   schema: string, image:string, image_alt:string, site_name:string,
     *   locale:string, type:string, robots:string
     * }
     */
    public function build(Page $page, array $tokens, string $h1, string $summary, array $faqs): array
    {
        $brand = $tokens['brand'] ?? '';
        $layanan = $tokens['layanan'] ?? '';
        $kelurahan = $tokens['kelurahan'] ?? '';
        $kecamatan = $tokens['kecamatan'] ?? '';
        $kota = $tokens['kota'] ?? '';

        $title = trim("{$layanan} di {$kelurahan}, {$kecamatan}, {$kota}")
            . ($brand !== '' ? " | {$brand}" : '');
        $description = Str::limit(preg_replace('/\s+/', ' ', $summary), 155, '…');

        $breadcrumb = $this->breadcrumb($page, $tokens);

        // CANONICAL CERDAS (anti halaman tipis): bila halaman miskin data lokal,
        // arahkan canonical ke HUB KECAMATAN agar sinyal terpusat dan Google
        // tidak melihat ribuan halaman kelurahan nyaris kembar sebagai duplikat.
        $canonical = $page->url();
        if (
            config('daya.thin_canonical_to_hub', true)
            && $this->uniqueness->isThin($page)
            && isset($breadcrumb[3]['url'])
            && $breadcrumb[3]['url'] !== ''
        ) {
            $canonical = $breadcrumb[3]['url']; // hub kecamatan
        }

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'h1' => $h1,
            'breadcrumb' => $breadcrumb,
            'schema' => $this->schemaJsonLd($page, $breadcrumb, $faqs),
            // --- Sinyal sosial & indexing (dipakai layouts/site.blade.php) ---
            'image' => $this->ogImage(),
            'image_alt' => $h1 !== '' ? $h1 : $title,
            'site_name' => Setting::get('brand_name', $brand),
            'locale' => Setting::get('og_locale', 'id_ID'),
            'type' => 'article',
            // Kontrol index: default index,follow. Set 'default_robots' = 'noindex,follow'
            // di Pengaturan untuk staging/soft-launch tanpa sentuh kode.
            'robots' => Setting::get('default_robots', 'index,follow'),
        ];
    }

    /**
     * URL gambar Open Graph/Twitter. Prioritas: og_image → hero_image →
     * organization_logo. Kosong bila tak satu pun diisi (tag di-skip di layout).
     */
    private function ogImage(): string
    {
        foreach (['og_image', 'hero_image', 'organization_logo', 'logo_image'] as $key) {
            $url = trim((string) Setting::get($key, ''));
            if ($url !== '') {
                return $url;
            }
        }

        return '';
    }

    /**
     * @return array<int,array{label:string,url:string}>
     */
    private function breadcrumb(Page $page, array $tokens): array
    {
        $servicePath = Str::slug($tokens['layanan'] ?? '');
        $kotaSlug = $page->city->slug ?? Str::slug($tokens['kota'] ?? '');
        $kecSlug = $page->district->slug ?? Str::slug($tokens['kecamatan'] ?? '');

        return [
            ['label' => 'Beranda', 'url' => url('/')],
            ['label' => $tokens['layanan'] ?? '', 'url' => url('/' . $servicePath)],
            ['label' => $tokens['kota'] ?? '', 'url' => url("/{$servicePath}/{$kotaSlug}")],
            ['label' => $tokens['kecamatan'] ?? '', 'url' => url("/{$servicePath}/{$kotaSlug}/{$kecSlug}")],
            ['label' => $tokens['kelurahan'] ?? '', 'url' => $page->url()],
        ];
    }

    /**
     * Gabungan JSON-LD: BreadcrumbList + FAQPage + Organization.
     */
    private function schemaJsonLd(Page $page, array $breadcrumb, array $faqs): string
    {
        $graph = [];

        // BreadcrumbList
        $graph[] = [
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(function ($crumb, $i) {
                return [
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'name' => $crumb['label'],
                    'item' => $crumb['url'],
                ];
            }, $breadcrumb, array_keys($breadcrumb)),
        ];

        // FAQPage
        if (! empty($faqs)) {
            $graph[] = [
                '@type' => 'FAQPage',
                'mainEntity' => array_map(fn ($f) => [
                    '@type' => 'Question',
                    'name' => $f['question'],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['answer']],
                ], $faqs),
            ];
        }

        // Organization
        $graph[] = [
            '@type' => 'Organization',
            'name' => Setting::get('organization_name', Setting::get('brand_name', '')),
            'url' => Setting::get('organization_url', url('/')),
            'logo' => Setting::get('organization_logo'),
        ];

        // Service + areaServed (PATCH SEO): menegaskan entitas "layanan X di
        // wilayah Y" untuk bisnis lokal — sinyal penting bagi Google/AI Overview.
        // Nama diambil MENTAH dari relasi (bukan token ter-escape) karena
        // json_encode sudah menangani escaping dengan aman.
        $page->loadMissing(['service', 'city', 'district', 'village']);
        $layananRaw = $page->service->name ?? '';
        $kotaRaw = $page->city->name ?? '';
        $kecamatanRaw = $page->district->name ?? '';
        $kelurahanRaw = $page->village->name ?? '';

        $graph[] = [
            '@type' => 'Service',
            'name' => trim("{$layananRaw} di {$kelurahanRaw}, {$kotaRaw}"),
            'serviceType' => $layananRaw,
            'url' => $page->url(),
            'provider' => [
                '@type' => 'Organization',
                'name' => Setting::get('organization_name', Setting::get('brand_name', '')),
                'url' => Setting::get('organization_url', url('/')),
            ],
            'areaServed' => array_values(array_filter([
                $kotaRaw !== '' ? ['@type' => 'City', 'name' => $kotaRaw] : null,
                $kecamatanRaw !== '' ? ['@type' => 'AdministrativeArea', 'name' => $kecamatanRaw] : null,
                $kelurahanRaw !== '' ? ['@type' => 'Place', 'name' => $kelurahanRaw] : null,
            ])),
        ];

        // LocalBusiness — sinyal SEO lokal yang kuat. Alamat/telepon dari
        // Pengaturan; koordinat (opsional) dari kolom CSV extra lat/lng.
        $localBusiness = [
            '@type' => 'LocalBusiness',
            'name' => trim(($layananRaw !== '' ? "{$layananRaw} — " : '') . Setting::get('organization_name', Setting::get('brand_name', ''))),
            'url' => $page->url(),
            'areaServed' => array_values(array_filter([
                $kotaRaw !== '' ? ['@type' => 'City', 'name' => $kotaRaw] : null,
                $kecamatanRaw !== '' ? ['@type' => 'AdministrativeArea', 'name' => $kecamatanRaw] : null,
            ])),
        ];
        if (($addr = trim((string) Setting::get('contact_address', ''))) !== '') {
            $localBusiness['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $addr,
                'addressLocality' => $kotaRaw,
                'addressCountry' => 'ID',
            ];
        }
        if (($phone = trim((string) Setting::get('contact_phone', ''))) !== '') {
            $localBusiness['telephone'] = $phone;
        }
        if (($img = trim((string) (Setting::get('og_image', '') ?: Setting::get('hero_image', '')))) !== '') {
            $localBusiness['image'] = $img;
        }
        if ($geo = ($this->geoFrom($page) ?: $this->regionGeo->forCity($kotaRaw))) {
            $localBusiness['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => $geo['lat'],
                'longitude' => $geo['lng'],
            ];
        }
        $graph[] = $localBusiness;

        $payload = [
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        ];

        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Ambil koordinat dari kolom CSV extra (lat/lng atau latitude/longitude).
     * Mengembalikan null bila tidak ada/tidak valid.
     *
     * @return array{lat:float,lng:float}|null
     */
    private function geoFrom(Page $page): ?array
    {
        $extra = $page->extra ?? [];
        if (! is_array($extra)) {
            return null;
        }

        $lat = $extra['lat'] ?? $extra['latitude'] ?? null;
        $lng = $extra['lng'] ?? $extra['longitude'] ?? null;

        if ($lat === null || $lng === null || ! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        return ['lat' => (float) $lat, 'lng' => (float) $lng];
    }
}
