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
        Schema::create('poll_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained('polls')->onDelete('cascade');
            $table->foreignId('poll_candidate_id')->constrained('poll_candidates')->onDelete('cascade');
            $table->foreignId('poll_participant_id')->constrained('poll_participants')->onDelete('cascade');
            $table->string('chat_id'); // Telegram chat ID
            $table->ipAddress('ip_address')->nullable(); // IP manzil
            $table->string('user_agent')->nullable(); // Browser/device info
            $table->timestamp('voted_at');
            $table->timestamps();

            // Bir foydalanuvchi bir so'rovnomada faqat bir marta ovoz berishi uchun
            $table->unique(['poll_id', 'chat_id']);

            // Indekslar
            $table->index('poll_id');
            $table->index('poll_candidate_id');
            $table->index('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poll_votes');
    }
};
