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
        Schema::table('poll_channel_posts', function (Blueprint $table) {
            $table->boolean('has_image')->default(false)->after('message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('poll_channel_posts', function (Blueprint $table) {
            $table->dropColumn('has_image');
        });
    }
};
