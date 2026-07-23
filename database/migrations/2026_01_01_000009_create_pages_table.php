<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel inti pSEO. Sengaja dibuat RAMPING agar 1.000.000+ baris tetap ringan.
     * Tidak menyimpan HTML/meta hasil render — semua dirender on-the-fly dari
     * template aktif + pool variasi (deterministik via variation_seed), sehingga
     * mengubah template otomatis berlaku ke seluruh halaman.
     */
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('city_id')->constrained('cities')->cascadeOnDelete();
            $table->foreignId('district_id')->constrained('districts')->cascadeOnDelete();
            $table->foreignId('village_id')->constrained('villages')->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('templates')->nullOnDelete();
            $table->foreignId('import_batch_id')->nullable()->constrained('import_batches')->nullOnDelete();

            // path penuh tanpa leading slash: les-privat-matematika/bandung/cicendo/pajajaran
            $table->string('path', 512);
            $table->unsignedInteger('variation_seed');
            // draft|published
            $table->string('status', 12)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique('path');
            $table->index('status');
            $table->index(['status', 'id']);
            $table->unique(['service_id', 'city_id', 'district_id', 'village_id'], 'pages_combo_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
