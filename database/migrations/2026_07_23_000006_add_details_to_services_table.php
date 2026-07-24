<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('price_from', 60)->nullable()->after('slug');
            $table->text('description')->nullable()->after('price_from');
            $table->string('image', 500)->nullable()->after('description');
            $table->unsignedSmallInteger('sort_order')->default(0)->after('image');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['price_from', 'description', 'image', 'sort_order']);
        });
    }
};
