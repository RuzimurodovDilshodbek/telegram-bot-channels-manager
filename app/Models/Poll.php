<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Poll extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'image',
        'start_date',
        'end_date',
        'is_active',
        'require_phone',
        'require_subscription',
        'enable_recaptcha',
        'required_channels',
        'total_votes',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
        'require_phone' => 'boolean',
        'require_subscription' => 'boolean',
        'enable_recaptcha' => 'boolean',
        'required_channels' => 'array',
        'total_votes' => 'integer',
    ];

    /**
     * So'rovnoma nomzodlari
     */
    public function candidates()
    {
        return $this->hasMany(PollCandidate::class)->orderBy('order');
    }

    /**
     * So'rovnoma ishtirokchilari
     */
    public function participants()
    {
        return $this->hasMany(PollParticipant::class);
    }

    /**
     * So'rovnoma ovozlari
     */
    public function votes()
    {
        return $this->hasMany(PollVote::class);
    }

    /**
     * Kanal postlari
     */
    public function channelPosts()
    {
        return $this->hasMany(PollChannelPost::class);
    }

    /**
     * So'rovnoma faol yoki yo'qligini tekshirish
     */
    public function isActive(): bool
    {
        return $this->is_active
            && now()->between($this->start_date, $this->end_date);
    }

    /**
     * So'rovnoma tugagan yoki yo'qligini tekshirish
     */
    public function isExpired(): bool
    {
        return now()->greaterThan($this->end_date);
    }

    /**
     * So'rovnoma boshlanish vaqti yetgan yoki yo'qligini tekshirish
     */
    public function hasStarted(): bool
    {
        return now()->greaterThanOrEqualTo($this->start_date);
    }
}
