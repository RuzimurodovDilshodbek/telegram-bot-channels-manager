<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        return $query->where('region_soato', $regionSoato);
    }

    // Helpers
    public function incrementPostsCount(): void
    {
        $this->increment('posts_count');
    }
}
