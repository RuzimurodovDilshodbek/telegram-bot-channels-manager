<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PollVote extends Model
{
    use HasFactory;

    protected $fillable = [
        'poll_id',
        'poll_candidate_id',
        'poll_participant_id',
        'chat_id',
        'ip_address',
        'user_agent',
        'voted_at',
    ];

    protected $casts = [
        'voted_at' => 'datetime',
    ];

    /**
     * So'rovnoma
     */
    public function poll()
    {
        return $this->belongsTo(Poll::class);
    }

    /**
     * Nomzod
     */
    public function candidate()
    {
        return $this->belongsTo(PollCandidate::class, 'poll_candidate_id');
    }

    /**
     * Ishtirokchi
     */
    public function participant()
    {
        return $this->belongsTo(PollParticipant::class, 'poll_participant_id');
    }
}
