<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ============================================
        // POLLS TABLE
        // ============================================
        Schema::table('polls', function (Blueprint $table) {
            // Faol so'rovnomalarni topish uchun (showActivePolls)
            // Query: where('is_active', true)->where('end_date', '>', now())
            $table->index(['is_active', 'end_date'], 'polls_active_end_date_idx');

            // Vaqt bo'yicha saralash uchun
            $table->index('created_at', 'polls_created_at_idx');
        });

        // ============================================
        // POLL_CANDIDATES TABLE
        // ============================================
        Schema::table('poll_candidates', function (Blueprint $table) {
            // Faol nomzodlarni tartib bo'yicha olish uchun
            // Query: where('poll_id', X)->where('is_active', true)->orderBy('order')
            $table->index(['poll_id', 'is_active', 'order'], 'poll_candidates_active_order_idx');

            // Ovozlar bo'yicha saralash uchun (natijalar ko'rsatish)
            // Query: where('poll_id', X)->orderBy('vote_count', 'desc')
            $table->index(['poll_id', 'vote_count'], 'poll_candidates_votes_idx');
        });

        // ============================================
        // POLL_PARTICIPANTS TABLE
        // ============================================
        Schema::table('poll_participants', function (Blueprint $table) {
            // Chat ID bo'yicha tez qidirish uchun
            $table->index('chat_id', 'poll_participants_chat_id_idx');

            // Tasdiqlangan ishtirokchilarni topish uchun
            $table->index('verified_at', 'poll_participants_verified_at_idx');

            // Telefon tasdiqlangan ishtirokchilar
            $table->index(['poll_id', 'phone_verified'], 'poll_participants_phone_verified_idx');

            // Obuna tasdiqlangan ishtirokchilar
            $table->index(['poll_id', 'subscription_verified'], 'poll_participants_subscription_idx');

            // Bot source bo'yicha statistika uchun
            $table->index('bot_source', 'poll_participants_bot_source_idx');
        });

        // ============================================
        // POLL_VOTES TABLE
        // ============================================
        Schema::table('poll_votes', function (Blueprint $table) {
            // Ovoz berilgan vaqt bo'yicha saralash
            $table->index('voted_at', 'poll_votes_voted_at_idx');

            // Ishtirokchi ovozlarini topish
            $table->index('poll_participant_id', 'poll_votes_participant_idx');
        });

        // ============================================
        // POLL_CHANNEL_POSTS TABLE
        // ============================================
        Schema::table('poll_channel_posts', function (Blueprint $table) {
            // Kanal postlarini tez yangilash uchun
            // Query: where('poll_id', X)->get()
            // poll_id va channel_id allaqachon index bor, lekin composite index tezroq
            $table->index(['poll_id', 'channel_id'], 'poll_channel_posts_composite_idx');

            // Oxirgi yangilangan postlarni topish
            $table->index('last_updated_at', 'poll_channel_posts_last_updated_idx');

            // Rasmli/rasmsiz postlarni ajratish
            $table->index(['poll_id', 'has_image'], 'poll_channel_posts_has_image_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('polls', function (Blueprint $table) {
            $table->dropIndex('polls_active_end_date_idx');
            $table->dropIndex('polls_created_at_idx');
        });

        Schema::table('poll_candidates', function (Blueprint $table) {
            $table->dropIndex('poll_candidates_active_order_idx');
            $table->dropIndex('poll_candidates_votes_idx');
        });

        Schema::table('poll_participants', function (Blueprint $table) {
            $table->dropIndex('poll_participants_chat_id_idx');
            $table->dropIndex('poll_participants_verified_at_idx');
            $table->dropIndex('poll_participants_phone_verified_idx');
            $table->dropIndex('poll_participants_subscription_idx');
            $table->dropIndex('poll_participants_bot_source_idx');
        });

        Schema::table('poll_votes', function (Blueprint $table) {
            $table->dropIndex('poll_votes_voted_at_idx');
            $table->dropIndex('poll_votes_participant_idx');
        });

        Schema::table('poll_channel_posts', function (Blueprint $table) {
            $table->dropIndex('poll_channel_posts_composite_idx');
            $table->dropIndex('poll_channel_posts_last_updated_idx');
            $table->dropIndex('poll_channel_posts_has_image_idx');
        });
    }
};
