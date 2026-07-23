<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Staging baris CSV. Memungkinkan generate berbasis queue yang dapat
     * di-pause/resume secara efisien (WHERE status=pending) tanpa men-scan ulang
     * file CSV (menghindari O(n^2)).
     */
    public function up(): void
    {
        Schema::create('import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained('import_batches')->cascadeOnDelete();
            $table->string('layanan');
            $table->string('kota');
            $table->string('kecamatan');
            $table->string('kelurahan');
            // pending|done|skipped|failed
            $table->string('status', 12)->default('pending');
            $table->string('error')->nullable();

            $table->index(['import_batch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_rows');
    }
};
