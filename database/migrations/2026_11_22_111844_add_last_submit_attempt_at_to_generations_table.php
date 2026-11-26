<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * submitted_at is when a picture got its slot. last_submit_attempt_at is
     * when we last actually tried to send it, so polling can tell a picture
     * whose send job is still retrying apart from one that was forgotten.
     */
    public function up(): void
    {
        Schema::table('generations', function (Blueprint $table) {
            $table->timestamp('last_submit_attempt_at')->nullable()->after('submitted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('generations', function (Blueprint $table) {
            $table->dropColumn('last_submit_attempt_at');
        });
    }
};
