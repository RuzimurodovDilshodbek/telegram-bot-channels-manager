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
        Schema::create('polls', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image')->nullable(); // Poll rasmi
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->boolean('is_active')->default(true);
            $table->boolean('require_phone')->default(true); // Telefon raqam talab qilish
            $table->boolean('require_subscription')->default(true); // Kanal obunasi talab qilish
            $table->boolean('enable_recaptcha')->default(true); // ReCaptcha yoqish
            $table->json('required_channels')->nullable(); // Majburiy kanallar chat_id ro'yxati
            $table->integer('total_votes')->default(0); // Jami ovozlar soni
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('polls');
    }
};
