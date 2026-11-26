<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The idempotency key comes from the Buy credits form, so sending the same
     * form twice only adds the credits once.
     */
    public function up(): void
    {
        Schema::create('credit_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount_nano');
            $table->uuid('idempotency_key')->unique();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_purchases');
    }
};
