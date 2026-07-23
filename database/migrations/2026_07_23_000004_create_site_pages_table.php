<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Halaman statis milik situs (Tentang Kami, Layanan, Kontak, Kebijakan, dll).
 *
 * Sebelumnya menu navigasi hanya berupa anchor (#layanan) yang menggulung di
 * halaman yang sama. Dengan tabel ini, tiap menu bisa menuju HALAMAN SUNGGUHAN
 * dengan URL sendiri — lebih baik untuk SEO (halaman terindeks bertambah,
 * internal linking menguat) dan lebih jelas bagi pengunjung.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 190)->unique();
            $table->string('title', 190);
            $table->string('menu_label', 60)->nullable();   // teks di navigasi
            $table->string('meta_description', 255)->nullable();
            $table->string('hero_image', 500)->nullable();
            $table->longText('content')->nullable();        // HTML, mendukung {{token}}
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('show_in_nav')->default(true);
            $table->boolean('show_in_footer')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_pages');
    }
};
