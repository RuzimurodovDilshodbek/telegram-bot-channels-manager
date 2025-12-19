<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PollParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'poll_id',
        'chat_id',
        'first_name',
        'last_name',
        'username',
        'phone',
        'bot_source',
        'ip_address',
        'phone_verified',
        'subscription_verified',
        'recaptcha_verified',
        'verified_at',
    ];

    protected $casts = [
        'phone_verified' => 'boolean',
        'subscription_verified' => 'boolean',
        'recaptcha_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    /**
     * So'rovnoma
     */
    public function poll()
    {
        return $this->belongsTo(Poll::class);
    }

    /**
     * Ishtirokchi ovozi
     */
    public function vote()
    {
        return $this->hasOne(PollVote::class);
    }

    /**
     * To'liq ism
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Ishtirokchi to'liq tasdiqlanganmi
     */
    public function isFullyVerified(): bool
    {
        $poll = $this->poll;

        $phoneVerified = !$poll->require_phone || $this->phone_verified;
        $subscriptionVerified = !$poll->require_subscription || $this->subscription_verified;
        $recaptchaVerified = !$poll->enable_recaptcha || $this->recaptcha_verified;

        return $phoneVerified && $subscriptionVerified && $recaptchaVerified;
    }

    /**
     * Ovoz bergan yoki bermaganligini tekshirish
     */
    public function hasVoted(): bool
    {
        return $this->vote()->exists();
    }
}
