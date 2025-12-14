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
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['management', 'main', 'region'])->comment('Kanal turi');
            $table->string('telegram_chat_id', 50)->unique()->comment('Telegram kanal ID');
            $table->string('name')->comment('Kanal nomi');
            $table->string('username')->nullable()->comment('Kanal username (@channel)');
            $table->string('region_soato', 10)->nullable()->comment('Region SOATO kodi (faqat region type uchun)');
            $table->boolean('is_active')->default(true)->comment('Kanal faol holati');
            $table->integer('posts_count')->default(0)->comment('Yuborilgan postlar soni');
            $table->text('description')->nullable()->comment('Kanal haqida');
            $table->timestamps();

            $table->index('type');
            $table->index('region_soato');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channels');
    }
};
