<?php

namespace App\Services;

use App\Models\City;
use App\Models\District;
use App\Models\Page;
use App\Models\Service;
use App\Models\Village;
use Illuminate\Support\Str;

/**
 * Mengubah satu baris CSV (layanan, kota, kecamatan, kelurahan) menjadi:
 * normalisasi hierarki lokasi (find-or-create) + satu baris `pages` (status draft).
 *
 * Cache lookup in-memory dipakai agar import ribuan baris tidak menembak DB
 * berulang kali untuk lokasi yang sama.
 */
class PageGenerator
{
    /** @var array<string,int> */
    private array $serviceCache = [];
    /** @var array<string,int> */
    private array $cityCache = [];
    /** @var array<string,int> */
    private array $districtCache = [];
    /** @var array<string,int> */
    private array $villageCache = [];

    /**
     * @param array{layanan:string,kota:string,kecamatan:string,kelurahan:string,extra?:?array} $row
     * @return array{created:bool, page:?Page}
     */
    public function generate(array $row, ?int $importBatchId = null): array
    {
        $extra = is_array($row['extra'] ?? null) && $row['extra'] !== [] ? $row['extra'] : null;
        $layanan = trim($row['layanan']);
        $kota = trim($row['kota']);
        $kecamatan = trim($row['kecamatan']);
        $kelurahan = trim($row['kelurahan']);

        if ($layanan === '' || $kota === '' || $kecamatan === '' || $kelurahan === '') {
            throw new \InvalidArgumentException('Kolom layanan/kota/kecamatan/kelurahan tidak boleh kosong.');
        }

        $serviceSlug = Str::slug($layanan);
        $citySlug = Str::slug($kota);
        $districtSlug = Str::slug($kecamatan);
        $villageSlug = Str::slug($kelurahan);

        $serviceId = $this->serviceCache[$serviceSlug] ??= Service::firstOrCreate(
            ['slug' => $serviceSlug],
            ['name' => $layanan]
        )->id;

        $cityId = $this->cityCache[$citySlug] ??= City::firstOrCreate(
            ['slug' => $citySlug],
            ['name' => $kota]
        )->id;

        $districtKey = $cityId . '|' . $districtSlug;
        $districtId = $this->districtCache[$districtKey] ??= District::firstOrCreate(
            ['city_id' => $cityId, 'slug' => $districtSlug],
            ['name' => $kecamatan]
        )->id;

        $villageKey = $districtId . '|' . $villageSlug;
        $villageId = $this->villageCache[$villageKey] ??= Village::firstOrCreate(
            ['district_id' => $districtId, 'slug' => $villageSlug],
            ['name' => $kelurahan]
        )->id;

        $path = "{$serviceSlug}/{$citySlug}/{$districtSlug}/{$villageSlug}";

        $page = Page::where('path', $path)->first();
        if ($page) {
            // Re-import kombinasi yang sama + kolom data lokal baru →
            // PERKAYA halaman yang sudah ada (merge, kolom baru menimpa lama).
            if ($extra !== null) {
                $page->update(['extra' => array_merge($page->extra ?? [], $extra)]);
            }

            return ['created' => false, 'page' => $page];
        }

        $page = Page::create([
            'service_id' => $serviceId,
            'city_id' => $cityId,
            'district_id' => $districtId,
            'village_id' => $villageId,
            'import_batch_id' => $importBatchId,
            'path' => $path,
            'variation_seed' => $this->seedFor($path),
            'extra' => $extra,
            'status' => Page::STATUS_DRAFT,
        ]);

        return ['created' => true, 'page' => $page];
    }

    /**
     * Seed deterministik & stabil dari path (regenerate kombinasi sama → seed sama).
     */
    private function seedFor(string $path): int
    {
        return (int) (sprintf('%u', crc32($path)) % 2147483647);
    }
}
