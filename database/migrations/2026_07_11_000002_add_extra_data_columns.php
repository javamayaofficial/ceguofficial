<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FITUR DATA LOKAL UNIK (naik level SEO).
     *
     * Kolom `extra` (JSON) menampung data tambahan per lokasi dari kolom CSV
     * opsional — mis. harga, jumlah_tutor, landmark, sekolah. Data ini:
     *  - tersedia sebagai token {{nama_kolom}} di template & variasi konten,
     *  - dirangkai otomatis menjadi blok {{fakta_lokal}},
     *  - dianyam ke AI Summary (→ meta description ikut unik per halaman).
     *
     * Inilah pembeda utama antara "halaman hasil spin" dan halaman yang layak
     * diindeks Google: fakta yang benar-benar berbeda antar lokasi.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->json('extra')->nullable()->after('variation_seed');
        });

        Schema::table('import_rows', function (Blueprint $table) {
            $table->json('extra')->nullable()->after('kelurahan');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('extra');
        });

        Schema::table('import_rows', function (Blueprint $table) {
            $table->dropColumn('extra');
        });
    }
};
