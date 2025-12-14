<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'bot_vacancy_id',
        'user_id',
        'telegram_user_id',
        'action',
        'comment',
        'action_at',
    ];

    protected $casts = [
        'telegram_user_id' => 'integer',
        'action_at' => 'datetime',
    ];

    // Relationships
    public function botVacancy(): BelongsTo
    {
        return $this->belongsTo(BotVacancy::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
