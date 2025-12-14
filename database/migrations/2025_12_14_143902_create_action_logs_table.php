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
        Schema::create('action_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_vacancy_id')->constrained('bot_vacancies')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->bigInteger('telegram_user_id')->nullable()->comment('Telegram user ID');
            $table->string('action')->comment('approved, rejected, published');
            $table->text('comment')->nullable()->comment('Izoh');
            $table->timestamp('action_at')->useCurrent()->comment('Harakat vaqti');
            $table->timestamps();

            $table->index('bot_vacancy_id');
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('action_logs');
    }
};
