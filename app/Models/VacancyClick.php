<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VacancyClick extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_post_id',
        'user_telegram_id',
        'ip_address',
        'user_agent',
        'referrer',
        'clicked_at',
    ];

    protected $casts = [
        'user_telegram_id' => 'integer',
        'clicked_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($click) {
            if (empty($click->clicked_at)) {
                $click->clicked_at = now();
            }
        });
    }

    // Relationships
    public function channelPost(): BelongsTo
    {
        return $this->belongsTo(ChannelPost::class);
    }
}
