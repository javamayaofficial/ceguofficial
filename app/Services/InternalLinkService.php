<?php

namespace App\Services;

use App\Models\Page;

/**
 * Internal linking otomatis (RFP): tampilkan "Layanan terkait" dan "Lokasi terkait"
 * untuk memperkuat crawling & SEO. Hanya menautkan halaman ber-status published.
 */
class InternalLinkService
{
    public function __construct(private readonly int $limit = 6)
    {
    }

    /**
     * @return array{services: array<int, array{label:string, url:string}>, locations: array<int, array{label:string, url:string}>}
     */
    public function relatedFor(Page $page): array
    {
        return [
            'services' => $this->relatedServices($page),
            'locations' => $this->relatedLocations($page),
        ];
    }

    /**
     * Layanan lain di kelurahan yang sama.
     */
    private function relatedServices(Page $page): array
    {
        return Page::query()
            ->published()
            ->where('village_id', $page->village_id)
            ->where('service_id', '!=', $page->service_id)
            ->with('service:id,name')
            ->limit($this->limit)
            ->get(['id', 'service_id', 'path'])
            ->map(fn (Page $p) => [
                'label' => trim(($p->service->name ?? 'Layanan') . ' ' . ($page->village->name ?? '')),
                'url' => url('/' . $p->path),
            ])->all();
    }

    /**
     * Layanan yang sama di kelurahan lain (kecamatan/kota yang sama).
     */
    private function relatedLocations(Page $page): array
    {
        return Page::query()
            ->published()
            ->where('service_id', $page->service_id)
            ->where('city_id', $page->city_id)
            ->where('village_id', '!=', $page->village_id)
            ->with('village:id,name')
            ->limit($this->limit)
            ->get(['id', 'village_id', 'path'])
            ->map(fn (Page $p) => [
                'label' => trim(($page->service->name ?? 'Layanan') . ' ' . ($p->village->name ?? '')),
                'url' => url('/' . $p->path),
            ])->all();
    }
}
