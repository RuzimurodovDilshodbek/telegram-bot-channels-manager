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
        Schema::create('poll_channel_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained('polls')->onDelete('cascade');
            $table->string('channel_id'); // Telegram channel ID
            $table->string('channel_username')->nullable(); // Channel username
            $table->string('message_id'); // Post message ID
            $table->text('post_text')->nullable(); // Post matni
            $table->timestamp('posted_at');
            $table->timestamp('last_updated_at')->nullable(); // Oxirgi yangilangan vaqt
            $table->integer('update_count')->default(0); // Necha marta yangilangan
            $table->timestamps();

            // Indekslar
            $table->index('poll_id');
            $table->index('channel_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poll_channel_posts');
    }
};
