<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Melengkapi tabel status indexing:
 *
 * - `source`       : dari mana status diketahui —
 *                    'analytics' (halaman muncul di hasil pencarian → pasti
 *                    terindeks, TANPA memakai kuota inspeksi) atau
 *                    'inspection' (URL Inspection API).
 * - `requested_at` : kapan owner menekan "Minta Index" di Search Console.
 *                    Google membatasi permintaan manual ~10-12 URL/hari, jadi
 *                    penandaan ini mencegah URL yang sama diminta berulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_index_statuses', function (Blueprint $table) {
            $table->string('source', 20)->nullable()->index()->after('coverage_state');
            $table->timestamp('requested_at')->nullable()->index()->after('checked_at');
        });
    }

    public function down(): void
    {
        Schema::table('page_index_statuses', function (Blueprint $table) {
            $table->dropColumn(['source', 'requested_at']);
        });
    }
};
