<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder utama.
 *
 * PENTING untuk instalasi multi-klien: secara default HANYA membuat akun admin.
 * Data contoh (konten demo & halaman sampel) TIDAK ikut, karena pada instalasi
 * klien sungguhan data itu harus dibersihkan manual dan mudah tercampur dengan
 * konten asli — persis masalah "FAQ demo bercampur konten baru".
 *
 * Untuk demo/uji coba, panggil eksplisit:
 *   php artisan db:seed --class=Database\\Seeders\\DemoSeeder
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
        ]);
    }
}
