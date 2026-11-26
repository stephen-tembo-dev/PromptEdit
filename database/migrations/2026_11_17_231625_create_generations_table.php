<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * We don't save the cost or the charge on this table on purpose. The cost
     * is worked out from the tokens and the rate card, and the charge is saved
     * once in ledger_entries.
     */
    public function up(): void
    {
        Schema::create('generations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('rate_card_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('provider_id')->nullable()->unique();

            $table->text('prompt');
            $table->string('size', 16);
            $table->string('quality', 16);

            $table->string('status', 16)->default('queued')->index();
            $table->unsignedSmallInteger('submit_attempts')->default(0);
            $table->timestamp('submitted_at')->nullable();

            $table->unsignedBigInteger('estimated_charge_nano');

            $table->unsignedInteger('input_text_tokens')->nullable();
            $table->unsignedInteger('input_image_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->unsignedInteger('reasoning_tokens')->nullable();

            $table->text('image_url')->nullable();
            $table->timestamp('image_url_expires_at')->nullable();

            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generations');
    }
};
