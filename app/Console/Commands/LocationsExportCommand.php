<?php

namespace App\Console\Commands;

use App\Services\RegionGeo;
use Illuminate\Console\Command;

/**
 * Ekspor CSV lokasi RESMI (kota,kecamatan,kelurahan) dari dataset terbundel
 * database/data/id-wilayah.csv (91k wilayah, sumber cahyadsn/wilayah).
 *
 * Ini pasangan generator keyword: keyword layanan (AI) × lokasi asli (ini) =
 * jutaan halaman TANPA halusinasi nama daerah. Ekspor bertahap (per provinsi/
 * kota) juga cara sehat menskalakan — jangan dump 83k sekaligus.
 *
 * Contoh:
 *   php artisan locations:export --province="Jawa Barat" --out=jabar.csv
 *   php artisan locations:export --city="Depok" --with-coords --out=depok.csv
 *   php artisan locations:export --level=kecamatan --province="Bali" --out=bali-kec.csv
 */
class LocationsExportCommand extends Command
{
    protected $signature = 'locations:export
        {--province= : Filter nama provinsi (opsional)}
        {--city= : Filter nama kota/kabupaten (opsional)}
        {--level=kelurahan : kelurahan|kecamatan}
        {--with-coords : Tambahkan kolom lat,lng (centroid kota)}
        {--out=locations.csv : Path file output}';

    protected $description = 'Ekspor CSV lokasi resmi (kota,kecamatan,kelurahan) untuk Import.';

    public function handle(RegionGeo $regionGeo): int
    {
        $path = base_path('database/data/id-wilayah.csv');
        if (! is_file($path)) {
            $this->error('Dataset tidak ditemukan: database/data/id-wilayah.csv');

            return self::FAILURE;
        }

        $level = $this->option('level') === 'kecamatan' ? 'kecamatan' : 'kelurahan';
        $withCoords = (bool) $this->option('with-coords');
        $provFilter = $this->norm((string) $this->option('province'));
        $cityFilter = $this->norm((string) $this->option('city'));

        // 1) Muat semua wilayah ke map by kode.
        $names = []; // kode => nama
        $h = fopen($path, 'r');
        fgetcsv($h);
        while (($r = fgetcsv($h)) !== false) {
            if (count($r) >= 2) {
                $names[$r[0]] = $r[1];
            }
        }
        fclose($h);

        // 2) Bangun baris denormalisasi.
        $out = fopen($this->option('out'), 'w');
        $header = ['kota', 'kecamatan', 'kelurahan'];
        if ($withCoords) {
            $header[] = 'lat';
            $header[] = 'lng';
        }
        fputcsv($out, $header);

        $count = 0;
        $coordCache = [];
        foreach ($names as $kode => $nama) {
            $depth = substr_count($kode, '.');
            // kelurahan = depth 3; kecamatan = depth 2 (bila level=kecamatan).
            if ($level === 'kelurahan' && $depth !== 3) {
                continue;
            }
            if ($level === 'kecamatan' && $depth !== 2) {
                continue;
            }

            $parts = explode('.', $kode);
            $provKode = $parts[0];
            $kotaKode = $parts[0] . '.' . ($parts[1] ?? '');
            $kecKode = $kotaKode . '.' . ($parts[2] ?? '');

            $kotaNama = $this->stripPrefix($names[$kotaKode] ?? '');
            $kecNama = $names[$kecKode] ?? '';
            $kelNama = $level === 'kelurahan' ? $nama : '';

            if ($provFilter !== '' && $this->norm($names[$provKode] ?? '') !== $provFilter) {
                continue;
            }
            if ($cityFilter !== '' && $this->norm($kotaNama) !== $cityFilter) {
                continue;
            }

            $row = [$kotaNama, $kecNama, $kelNama];
            if ($withCoords) {
                $geo = $coordCache[$kotaNama] ??= $regionGeo->forCity($kotaNama);
                $row[] = $geo['lat'] ?? '';
                $row[] = $geo['lng'] ?? '';
            }
            fputcsv($out, $row);
            $count++;
        }
        fclose($out);

        $this->info("Selesai. {$count} baris → " . $this->option('out'));
        if ($count === 0) {
            $this->warn('0 baris — periksa ejaan --province/--city (harus sesuai nama resmi).');
        }

        return self::SUCCESS;
    }

    private function stripPrefix(string $name): string
    {
        return preg_replace('/^(Kota Administrasi|Kabupaten Administrasi|Kotamadya|Kabupaten|Kota|Kab\.?)\s+/i', '', trim($name)) ?? $name;
    }

    private function norm(string $s): string
    {
        $s = mb_strtolower(trim($s));

        return preg_replace('/\s+/', ' ', $this->stripPrefix($s)) ?? $s;
    }
}
