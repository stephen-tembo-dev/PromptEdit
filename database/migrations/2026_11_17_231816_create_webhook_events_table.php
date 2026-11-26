<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * event_id is unique, so a duplicate message just gets ignored when we save it.
     * payload is the message body exactly as Pixelforge sent it.
     */
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();
            $table->longText('payload');
            $table->timestamp('received_at')->useCurrent();
            $table->timestamp('processed_at')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
