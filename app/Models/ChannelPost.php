<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ChannelPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'bot_vacancy_id',
        'channel_id',
        'telegram_message_id',
        'unique_tracking_code',
        'clicks_count',
        'posted_at',
    ];

    protected $casts = [
        'telegram_message_id' => 'integer',
        'clicks_count' => 'integer',
        'posted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($channelPost) {
            if (empty($channelPost->unique_tracking_code)) {
                $channelPost->unique_tracking_code = self::generateUniqueCode();
            }
            if (empty($channelPost->posted_at)) {
                $channelPost->posted_at = now();
            }
        });
    }

    // Relationships
    public function botVacancy(): BelongsTo
    {
        return $this->belongsTo(BotVacancy::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function vacancyClicks(): HasMany
    {
        return $this->hasMany(VacancyClick::class);
    }

    // Helpers
    public static function generateUniqueCode(): string
    {
        $length = config('tracking.code.length', 12);

        do {
            $code = Str::random($length);
        } while (self::where('unique_tracking_code', $code)->exists());

        return $code;
    }

    public function getTrackingUrlAttribute(): string
    {
        return config('tracking.url') . '/' . $this->unique_tracking_code;
    }

    public function incrementClicksCount(): void
    {
        $this->increment('clicks_count');
    }
}
