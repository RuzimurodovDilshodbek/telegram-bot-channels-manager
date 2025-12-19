<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PollCandidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'poll_id',
        'name',
        'description',
        'photo',
        'vote_count',
        'order',
        'is_active',
    ];

    protected $casts = [
        'vote_count' => 'integer',
        'order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * So'rovnoma
     */
    public function poll()
    {
        return $this->belongsTo(Poll::class);
    }

    /**
     * Nomzodga berilgan ovozlar
     */
    public function votes()
    {
        return $this->hasMany(PollVote::class);
    }

    /**
     * Ovoz foizini hisoblash
     */
    public function getVotePercentageAttribute(): float
    {
        $totalVotes = $this->poll->total_votes;

        if ($totalVotes == 0) {
            return 0;
        }

        return round(($this->vote_count / $totalVotes) * 100, 2);
    }
}
