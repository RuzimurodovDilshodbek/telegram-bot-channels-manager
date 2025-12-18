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
        Schema::create('channel_admins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')
                ->constrained('channels')
                ->cascadeOnDelete()
                ->comment('Qaysi kanal uchun admin');
            $table->bigInteger('telegram_user_id')->comment('Telegram foydalanuvchi ID');
            $table->string('telegram_username')->nullable()->comment('Telegram @username');
            $table->string('name')->comment('Admin ismi');
            $table->boolean('is_active')->default(true)->comment('Faollik holati');
            $table->timestamps();

            // Indexes
            $table->index('channel_id');
            $table->index('telegram_user_id');
            $table->index('is_active');

            // Unique constraint: bir admin bir kanalda faqat bir marta
            $table->unique(['channel_id', 'telegram_user_id'], 'unique_channel_admin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channel_admins');
    }
};
