<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PELACAKAN LEAD: mencatat setiap klik tombol WhatsApp beserta konteks halaman
 * (path, layanan, kota). Dari sini pemilik tahu halaman/kota/layanan mana yang
 * benar-benar menghasilkan chat — dasar untuk menggandakan yang menang.
 *
 * Sengaja ramping & hanya created_at (append-only). Untuk volume sangat besar,
 * agregasi bisa dipindah ke tabel ringkasan harian nanti.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_clicks', function (Blueprint $table) {
            $table->id();
            $table->string('page_path', 512)->index();
            $table->string('service', 190)->nullable();
            $table->string('city', 190)->nullable();
            $table->string('source', 40)->nullable(); // float | nav | inline
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_clicks');
    }
};
