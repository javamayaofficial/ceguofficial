<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Services\PageGenerator;
use Illuminate\Database\Seeder;

/**
 * Membuat beberapa halaman contoh dari CSV demo lalu mem-publish-nya, agar
 * sistem langsung bisa dilihat hasilnya tanpa perlu upload manual.
 */
class SamplePagesSeeder extends Seeder
{
    public function run(): void
    {
        $generator = new PageGenerator();
        $csv = database_path('seeders/data/sample-locations.csv');
        $handle = fopen($csv, 'r');
        $header = array_map('trim', fgetcsv($handle));
        $idx = array_flip($header);

        while (($row = fgetcsv($handle)) !== false) {
            if ($row === [null]) {
                continue;
            }
            $result = $generator->generate([
                'layanan' => $row[$idx['layanan']],
                'kota' => $row[$idx['kota']],
                'kecamatan' => $row[$idx['kecamatan']],
                'kelurahan' => $row[$idx['kelurahan']],
            ]);

            // Publish langsung untuk demo.
            $result['page']->update([
                'status' => Page::STATUS_PUBLISHED,
                'published_at' => now(),
            ]);
        }
        fclose($handle);

        $this->command->info('Sample pages: ' . Page::count() . ' halaman (published).');
    }
}
