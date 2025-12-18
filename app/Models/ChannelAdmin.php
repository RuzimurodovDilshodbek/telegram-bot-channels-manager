<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ChannelAdmin extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_id',
        'telegram_user_id',
        'telegram_username',
        'name',
        'is_active',
    ];

    protected $casts = [
        'channel_id' => 'integer',
        'telegram_user_id' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Relationships
     */

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'telegram_user_id', 'telegram_id');
    }

    /**
     * Scopes
     */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByTelegramUserId(Builder $query, int $telegramUserId): Builder
    {
        return $query->where('telegram_user_id', $telegramUserId);
    }

    public function scopeByChannel(Builder $query, int $channelId): Builder
    {
        return $query->where('channel_id', $channelId);
    }

    /**
     * Helper Methods
     */

    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Check if a Telegram user ID is an active admin for a specific channel
     */
    public static function isAdminForChannel(int $telegramUserId, int $channelId): bool
    {
        return static::active()
            ->byTelegramUserId($telegramUserId)
            ->byChannel($channelId)
            ->exists();
    }

    /**
     * Get admin record by Telegram user ID and channel ID
     */
    public static function findByTelegramUserIdAndChannel(int $telegramUserId, int $channelId): ?self
    {
        return static::byTelegramUserId($telegramUserId)
            ->byChannel($channelId)
            ->first();
    }

    /**
     * Get all channels where this Telegram user is an active admin
     */
    public static function getChannelsForAdmin(int $telegramUserId)
    {
        return static::active()
            ->byTelegramUserId($telegramUserId)
            ->with('channel')
            ->get()
            ->pluck('channel');
    }
}
