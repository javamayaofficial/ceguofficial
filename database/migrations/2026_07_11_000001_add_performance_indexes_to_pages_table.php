<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PATCH PERFORMA (skala 1–2 juta baris).
     *
     * InternalLinkService memfilter:
     *  - relatedServices():  WHERE village_id = ? AND status = 'published'
     *  - relatedLocations(): WHERE service_id = ? AND city_id = ? AND status = 'published'
     *
     * Tanpa indeks komposit ini, MySQL hanya bisa memakai indeks FK kolom
     * tunggal lalu menyaring status baris-per-baris. Di 2 juta baris dan
     * traffic crawler tinggi, dua query ini menjadi hotspot terbesar.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->index(['village_id', 'status', 'service_id'], 'pages_village_status_idx');
            $table->index(['service_id', 'city_id', 'status'], 'pages_service_city_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropIndex('pages_village_status_idx');
            $table->dropIndex('pages_service_city_status_idx');
        });
    }
};
