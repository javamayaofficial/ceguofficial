<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Resolusi nama kota/kabupaten → koordinat resmi (lat/lng), dari dataset
 * terbundel database/data/id-region-coords.csv (sumber terbuka cahyadsn/wilayah:
 * 38 provinsi + 513 kabupaten/kota).
 *
 * Dipakai SeoService untuk mengisi GeoCoordinates pada schema LocalBusiness
 * secara massal — tanpa API geocoding berbayar, tanpa halusinasi.
 */
class RegionGeo
{
    private const CACHE_KEY = 'region_geo_map';
    private const CSV = 'database/data/id-region-coords.csv';

    /**
     * Cari koordinat untuk sebuah nama kota/kabupaten.
     *
     * @return array{lat:float,lng:float}|null
     */
    public function forCity(string $city): ?array
    {
        $key = $this->normalize($city);
        if ($key === '') {
            return null;
        }

        return $this->map()[$key] ?? null;
    }

    /**
     * Map ternormalisasi: 'jakarta selatan' => ['lat'=>..,'lng'=>..].
     *
     * @return array<string,array{lat:float,lng:float}>
     */
    private function map(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $path = base_path(self::CSV);
            if (! is_file($path)) {
                return [];
            }

            $map = [];
            if (($h = fopen($path, 'r')) !== false) {
                fgetcsv($h); // header
                while (($row = fgetcsv($h)) !== false) {
                    if (count($row) < 4) {
                        continue;
                    }
                    [, $nama, $lat, $lng] = $row;
                    $key = $this->normalize((string) $nama);
                    if ($key === '' || ! is_numeric($lat) || ! is_numeric($lng)) {
                        continue;
                    }
                    // Baris pertama (kabupaten/kota) menang atas duplikat nama.
                    $map[$key] ??= ['lat' => (float) $lat, 'lng' => (float) $lng];
                }
                fclose($h);
            }

            return $map;
        });
    }

    /**
     * Samakan bentuk nama agar cocok dengan input admin: buang prefiks
     * administratif, huruf kecil, rapatkan spasi.
     */
    private function normalize(string $name): string
    {
        $name = mb_strtolower(trim($name));
        $name = preg_replace('/^(kota administrasi|kabupaten administrasi|kotamadya|kabupaten|kota|kab\.?)\s+/u', '', $name) ?? $name;
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;

        return trim($name);
    }
}
