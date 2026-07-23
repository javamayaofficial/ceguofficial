<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Melengkapi pelacakan klik WhatsApp:
 *
 * - `wa_number` : nomor CS mana yang menerima klik (mesin memakai ROTATOR —
 *                 satu Pengaturan boleh berisi banyak nomor, dipilih
 *                 deterministik per halaman). Dengan ini terlihat sebaran beban
 *                 antar CS, dan bisa dipastikan rotatornya merata.
 *
 * - `token`     : penanda acak dari browser, dipakai untuk mencocokkan klik
 *                 dengan konfirmasi pembukaan.
 *
 * - `opened_at` : diisi bila browser berpindah ke latar belakang sesaat setelah
 *                 klik — pertanda aplikasi WhatsApp benar-benar terbuka.
 *                 Ini PROKSI, bukan kepastian: isi percakapan tetap tidak
 *                 dilacak, dan tidak semua perangkat mengirim sinyal ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_clicks', function (Blueprint $table) {
            $table->string('wa_number', 30)->nullable()->index()->after('city');
            $table->string('token', 40)->nullable()->index()->after('source');
            $table->timestamp('opened_at')->nullable()->after('token');
        });
    }

    public function down(): void
    {
        Schema::table('lead_clicks', function (Blueprint $table) {
            $table->dropColumn(['wa_number', 'token', 'opened_at']);
        });
    }
};
