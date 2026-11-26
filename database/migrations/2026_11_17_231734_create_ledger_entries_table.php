<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Amounts are always positive. The type decides whether money goes in or out.
     * The unique (generation_id, type) rule means the database itself won't let a
     * picture be held, charged or released twice.
     */
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generation_id')->constrained()->restrictOnDelete();
            $table->string('type', 16);
            $table->unsignedBigInteger('amount_nano');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['generation_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
