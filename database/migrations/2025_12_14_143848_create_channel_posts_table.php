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
        Schema::create('channel_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_vacancy_id')->constrained('bot_vacancies')->onDelete('cascade');
            $table->foreignId('channel_id')->constrained('channels')->onDelete('cascade');
            $table->bigInteger('telegram_message_id')->comment('Kanalda yuborilgan xabar ID');
            $table->string('unique_tracking_code', 20)->unique()->comment('Click tracking uchun unique kod');
            $table->integer('clicks_count')->default(0)->comment('Click soni');
            $table->timestamp('posted_at')->comment('Yuborilgan vaqt');
            $table->timestamps();

            $table->index(['bot_vacancy_id', 'channel_id']);
            $table->index('unique_tracking_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channel_posts');
    }
};
