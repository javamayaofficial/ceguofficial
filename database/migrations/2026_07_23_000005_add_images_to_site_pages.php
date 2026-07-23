<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Slot gambar tambahan untuk halaman statis (Tentang Kami, Layanan, Kontak).
 *
 * Sebelumnya hanya ada satu `hero_image`. Sekarang tersedia 4 gambar isi yang
 * bisa disisipkan di mana pun dalam konten lewat token {{gambar1}}..{{gambar4}},
 * plus galeri otomatis di bagian bawah bila token tidak dipakai.
 *
 * Alt text tiap gambar diambil dari kolomnya sendiri; bila kosong, dibuatkan
 * dari judul halaman + urutan sehingga tetap deskriptif dan tidak duplikat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_pages', function (Blueprint $table) {
            for ($i = 1; $i <= 4; $i++) {
                $table->string("image_{$i}", 500)->nullable()->after('hero_image');
                $table->string("image_{$i}_alt", 190)->nullable()->after("image_{$i}");
            }
        });
    }

    public function down(): void
    {
        Schema::table('site_pages', function (Blueprint $table) {
            $kolom = [];
            for ($i = 1; $i <= 4; $i++) {
                $kolom[] = "image_{$i}";
                $kolom[] = "image_{$i}_alt";
            }
            $table->dropColumn($kolom);
        });
    }
};
