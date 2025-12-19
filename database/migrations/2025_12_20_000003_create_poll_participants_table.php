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
        Schema::create('poll_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained('polls')->onDelete('cascade');
            $table->string('chat_id'); // Telegram chat ID
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('username')->nullable();
            $table->string('phone')->nullable(); // Telefon raqam
            $table->string('bot_source')->default('poll_bot'); // Qaysi botdan kelgan
            $table->ipAddress('ip_address')->nullable(); // IP manzil
            $table->boolean('phone_verified')->default(false); // Telefon tasdiqlangan
            $table->boolean('subscription_verified')->default(false); // Obuna tasdiqlangan
            $table->boolean('recaptcha_verified')->default(false); // ReCaptcha tasdiqlangan
            $table->timestamp('verified_at')->nullable(); // Tasdiqlangan vaqt
            $table->timestamps();

            // Bir so'rovnomada bir foydalanuvchi faqat bir marta qatnashishi uchun
            $table->unique(['poll_id', 'chat_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poll_participants');
    }
};
