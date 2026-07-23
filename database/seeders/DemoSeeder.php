<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Data CONTOH untuk demo/uji coba saja — JANGAN dijalankan di situs klien
 * produksi. Berisi variasi konten generik dan halaman sampel yang harus
 * dibersihkan sebelum konten asli dimasukkan.
 *
 *   php artisan db:seed --class=Database\\Seeders\\DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ContentSeeder::class,
            SamplePagesSeeder::class,
        ]);
    }
}
