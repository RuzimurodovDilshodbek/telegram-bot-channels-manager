<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('polls', function (Blueprint $table) {
            $table->index(['is_active', 'end_date'], 'polls_active_end_date_idx');
            $table->index('created_at', 'polls_created_at_idx');
        });

        Schema::table('poll_candidates', function (Blueprint $table) {
            $table->index(['poll_id', 'is_active', 'order'], 'poll_candidates_active_order_idx');
            $table->index(['poll_id', 'vote_count'], 'poll_candidates_votes_idx');
        });

        Schema::table('poll_participants', function (Blueprint $table) {
            $table->index('chat_id', 'poll_participants_chat_id_idx');
            $table->index('verified_at', 'poll_participants_verified_at_idx');
            $table->index(['poll_id', 'phone_verified'], 'poll_participants_phone_verified_idx');
            $table->index(['poll_id', 'subscription_verified'], 'poll_participants_subscription_idx');
            $table->index('bot_source', 'poll_participants_bot_source_idx');
        });

        Schema::table('poll_votes', function (Blueprint $table) {
            $table->index('voted_at', 'poll_votes_voted_at_idx');
            $table->index('poll_participant_id', 'poll_votes_participant_idx');
        });

        Schema::table('poll_channel_posts', function (Blueprint $table) {
            $table->index(['poll_id', 'channel_id'], 'poll_channel_posts_composite_idx');
            $table->index('last_updated_at', 'poll_channel_posts_last_updated_idx');
            $table->index(['poll_id', 'has_image'], 'poll_channel_posts_has_image_idx');
        });
    }

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
