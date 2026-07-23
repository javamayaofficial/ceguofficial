<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menyimpan hasil URL Inspection API per halaman, agar tidak perlu memanggil
 * Google berulang kali (kuota inspeksi terbatas 2.000/hari per properti).
 *
 * Hasil disimpan apa adanya dari Google:
 *   verdict        : PASS | PARTIAL | FAIL | NEUTRAL
 *   coverage_state : mis. "Submitted and indexed", "Discovered - currently not
 *                    indexed", "Crawled - currently not indexed", dsb.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_index_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('verdict', 20)->nullable()->index();
            $table->string('coverage_state', 190)->nullable()->index();
            $table->string('robots_state', 60)->nullable();
            $table->timestamp('last_crawl_at')->nullable();
            $table->timestamp('checked_at')->nullable()->index();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->unique('page_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_index_statuses');
    }
};
