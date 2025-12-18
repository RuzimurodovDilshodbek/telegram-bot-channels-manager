<?php

namespace App\Services;

use App\Models\ChannelAdmin;
use App\Models\User;
use App\Models\Channel;

class ChannelAdminService
{
    /**
     * Check if a Telegram user is authorized to perform admin actions for a specific channel
     */
    public function isAuthorizedForChannel(int $telegramUserId, int $channelId): bool
    {
        return ChannelAdmin::isAdminForChannel($telegramUserId, $channelId);
    }

    /**
     * Check if current Filament user is a channel admin for a specific channel
     */
    public function isFilamentUserAdminForChannel(?int $userId = null, ?int $channelId = null): bool
    {
        $userId = $userId ?? auth()->id();

        if (!$userId) {
            return false;
        }

        $user = User::find($userId);

        if (!$user || !$user->telegram_id) {
            return false;
        }

        // If no specific channel, check if user is admin for any channel
        if ($channelId === null) {
            return ChannelAdmin::active()
                ->byTelegramUserId($user->telegram_id)
                ->exists();
        }

        return ChannelAdmin::isAdminForChannel($user->telegram_id, $channelId);
    }

    /**
     * Get all admin records for a Filament user
     */
    public function getAdminRecordsForUser(?int $userId = null)
    {
        $userId = $userId ?? auth()->id();

        if (!$userId) {
            return collect();
        }

        $user = User::find($userId);

        if (!$user || !$user->telegram_id) {
            return collect();
        }

        return ChannelAdmin::active()
            ->byTelegramUserId($user->telegram_id)
            ->with('channel')
            ->get();
    }

    /**
     * Get all channels where a Telegram user is an admin
     */
    public function getChannelsForAdmin(int $telegramUserId)
    {
        return ChannelAdmin::getChannelsForAdmin($telegramUserId);
    }

    /**
     * Get admin record for current Filament user and specific channel
     */
    public function getAdminForFilamentUser(?int $userId = null, ?int $channelId = null): ?ChannelAdmin
    {
        $userId = $userId ?? auth()->id();

        if (!$userId || !$channelId) {
            return null;
        }

        $user = User::find($userId);

        if (!$user || !$user->telegram_id) {
            return null;
        }

        return ChannelAdmin::findByTelegramUserIdAndChannel($user->telegram_id, $channelId);
    }

    /**
     * Check if Filament user is admin for management channel (for vacancy approval)
     */
    public function isFilamentUserAdminForManagementChannel(?int $userId = null): bool
    {
        $managementChannel = Channel::management()->active()->first();

        if (!$managementChannel) {
            return false;
        }

        return $this->isFilamentUserAdminForChannel($userId, $managementChannel->id);
    }
}
