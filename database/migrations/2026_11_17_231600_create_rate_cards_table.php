<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A rate card is one set of Pixelforge prices, in nanodollars per million
     * tokens ($12.50 is saved as 12,500,000,000). Each picture points to the
     * prices it was made with. The same set of prices is only saved once.
     */
    public function up(): void
    {
        Schema::create('rate_cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('input_text_nano_per_million');
            $table->unsignedBigInteger('input_image_nano_per_million');
            $table->unsignedBigInteger('output_nano_per_million');
            $table->unsignedBigInteger('reasoning_nano_per_million');
            $table->timestamp('created_at')->useCurrent();

            $table->unique([
                'input_text_nano_per_million',
                'input_image_nano_per_million',
                'output_nano_per_million',
                'reasoning_nano_per_million',
            ], 'rate_cards_rates_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rate_cards');
    }
};
