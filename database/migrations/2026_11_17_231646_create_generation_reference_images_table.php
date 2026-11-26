<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('generation_reference_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generation_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->text('url');

            $table->unique(['generation_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generation_reference_images');
    }
};
