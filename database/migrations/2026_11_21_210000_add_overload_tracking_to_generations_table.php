<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * overload_attempts only counts "overloaded" answers, so waiting for a rate
     * limit doesn't use up the tries.
     *
     * may_exist_at_provider is set when a try got no clear answer (the connection
     * dropped, or a 5xx error). Pixelforge might have made the picture, so we
     * never fail it on our side alone.
     */
    public function up(): void
    {
        Schema::table('generations', function (Blueprint $table) {
            $table->unsignedSmallInteger('overload_attempts')->default(0)->after('submit_attempts');
            $table->boolean('may_exist_at_provider')->default(false)->after('overload_attempts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('generations', function (Blueprint $table) {
            $table->dropColumn(['overload_attempts', 'may_exist_at_provider']);
        });
    }
};
