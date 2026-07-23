<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FITUR CROSS-JOIN LAYANAN: satu file CSV lokasi (kota,kecamatan,kelurahan)
     * dapat digandakan otomatis untuk BANYAK layanan/keyword sekaligus —
     * daftar layanan diisi di form upload, tidak perlu copy-paste jutaan baris
     * di spreadsheet.
     */
    public function up(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            $table->text('layanan_list')->nullable()->after('stored_path');
        });
    }

    public function down(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            $table->dropColumn('layanan_list');
        });
    }
};
