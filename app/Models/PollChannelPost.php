<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PollChannelPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'poll_id',
        'channel_id',
        'channel_username',
        'message_id',
        'has_image',
        'post_text',
        'posted_at',
        'last_updated_at',
        'update_count',
    ];

    protected $casts = [
        'has_image' => 'boolean',
        'posted_at' => 'datetime',
        'last_updated_at' => 'datetime',
        'update_count' => 'integer',
    ];

    /**
     * So'rovnoma
     */
    public function poll()
    {
        return $this->belongsTo(Poll::class);
    }
}
