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
        Schema::create('vacancy_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_post_id')->constrained('channel_posts')->onDelete('cascade');
            $table->bigInteger('user_telegram_id')->nullable()->comment('Telegram user ID');
            $table->string('ip_address', 45)->nullable()->comment('IP address');
            $table->text('user_agent')->nullable()->comment('User agent');
            $table->string('referrer')->nullable()->comment('Referrer URL');
            $table->timestamp('clicked_at')->comment('Click vaqti');
            $table->timestamps();

            $table->index('channel_post_id');
            $table->index('ip_address');
            $table->index('clicked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vacancy_clicks');
    }
};
