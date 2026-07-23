<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Akses dataset wilayah resmi Indonesia (database/data/id-wilayah.csv,
 * 91.162 baris: provinsi → kota/kabupaten → kecamatan → kelurahan).
 *
 * Dipakai wizard "Mulai Cepat" agar owner cukup MEMILIH wilayah dari daftar —
 * tidak perlu menyiapkan CSV lokasi sendiri. Nama wilayah diambil dari data
 * resmi, jadi tidak ada risiko nama daerah dikarang.
 */
class RegionDataset
{
    private const CSV = 'database/data/id-wilayah.csv';
    private const CACHE_KEY = 'region_dataset_index';

    /**
     * Indeks kode => nama (di-cache karena file besar).
     *
     * @return array<string,string>
     */
    private static function index(): array
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
                    if (count($row) >= 2) {
                        $map[$row[0]] = $row[1];
                    }
                }
                fclose($h);
            }

            return $map;
        });
    }

    /**
     * Daftar provinsi: ['32' => 'Jawa Barat', …]
     *
     * @return array<string,string>
     */
    public static function provinces(): array
    {
        $out = [];
        foreach (self::index() as $kode => $nama) {
            if (! str_contains($kode, '.')) {
                $out[$kode] = $nama;
            }
        }
        asort($out);

        return $out;
    }

    /**
     * Daftar kota/kabupaten: ['32.76' => ['nama' => 'Depok', 'prov' => '32'], …]
     *
     * @return array<string,array{nama:string,prov:string}>
     */
    public static function cities(): array
    {
        $out = [];
        foreach (self::index() as $kode => $nama) {
            if (substr_count($kode, '.') === 1) {
                $out[$kode] = ['nama' => self::stripPrefix($nama), 'prov' => explode('.', $kode)[0]];
            }
        }

        return $out;
    }

    /**
     * Baris lokasi siap import untuk satu kota/kabupaten.
     *
     * @param string $cityKode kode level-2, mis. '32.76'
     * @param string $level    'kelurahan' | 'kecamatan'
     * @return array<int,array{kota:string,kecamatan:string,kelurahan:string}>
     */
    public static function rows(string $cityKode, string $level = 'kelurahan'): array
    {
        $index = self::index();
        $kotaNama = self::stripPrefix($index[$cityKode] ?? '');
        if ($kotaNama === '') {
            return [];
        }

        $depth = $level === 'kecamatan' ? 2 : 3;
        $rows = [];

        foreach ($index as $kode => $nama) {
            if (substr_count($kode, '.') !== $depth || ! str_starts_with($kode, $cityKode . '.')) {
                continue;
            }

            $parts = explode('.', $kode);
            $kecKode = $parts[0] . '.' . $parts[1] . '.' . ($parts[2] ?? '');

            $rows[] = [
                'kota' => $kotaNama,
                'kecamatan' => $index[$kecKode] ?? '',
                'kelurahan' => $depth === 3 ? $nama : '',
            ];
        }

        usort($rows, fn ($a, $b) => [$a['kecamatan'], $a['kelurahan']] <=> [$b['kecamatan'], $b['kelurahan']]);

        return $rows;
    }

    /**
     * Nama kota tanpa prefiks administratif. "Kabupaten X" dipertahankan bila
     * ada padanan "Kota X" agar URL-nya tidak bertabrakan.
     */
    public static function stripPrefix(string $name): string
    {
        $name = trim($name);

        // Kabupaten dipertahankan sebagai pembeda (Bogor vs Kabupaten Bogor).
        if (preg_match('/^Kabupaten\s+/i', $name)) {
            return preg_replace('/^Kabupaten\s+Administrasi\s+/i', 'Kabupaten ', $name) ?? $name;
        }

        return preg_replace('/^(Kota\s+Administrasi|Kotamadya|Kota)\s+/i', '', $name) ?? $name;
    }
}
