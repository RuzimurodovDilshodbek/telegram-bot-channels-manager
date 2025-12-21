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
        Schema::table('poll_participants', function (Blueprint $table) {
            $table->boolean('ip_verified')->default(false)->after('recaptcha_verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('poll_participants', function (Blueprint $table) {
            $table->dropColumn('ip_verified');
        });
    }
};
