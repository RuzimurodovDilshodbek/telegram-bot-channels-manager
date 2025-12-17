<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Channel extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'telegram_chat_id',
        'name',
        'username',
        'region_soato',
        'is_active',
        'posts_count',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'posts_count' => 'integer',
        'region_soato' => 'array',
    ];

    // Relationships
    public function channelPosts(): HasMany
    {
        return $this->hasMany(ChannelPost::class);
    }



    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeManagement($query)
    {
        return $query->where('type', 'management');
    }

    public function scopeMain($query)
    {
        return $query->where('type', 'main');
    }

    public function scopeRegion($query)
    {
        return $query->where('type', 'region');
    }

    public function scopeByRegion($query, $regionSoato)
    {
        // Check if region_soato JSONB array contains the value
        return $query->whereRaw('region_soato @> ?', [json_encode([$regionSoato])]);
    }

    // Helpers
    public function incrementPostsCount(): void
    {
        $this->increment('posts_count');
    }
}
