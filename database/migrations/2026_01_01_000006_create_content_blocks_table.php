<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_blocks', function (Blueprint $table) {
            $table->id();
            // hero|intro|pain_point|solusi|usp|testimoni|cta|about
            $table->string('section', 32);
            $table->text('content');
            $table->unsignedInteger('weight')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['section', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_blocks');
    }
};
