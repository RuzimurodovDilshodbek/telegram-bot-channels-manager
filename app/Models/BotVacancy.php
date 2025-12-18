<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotVacancy extends Model
{
    use HasFactory;

    protected $fillable = [
        'oson_ish_vacancy_id',
        'source',
        'status',
        'title',
        'company_name',
        'region_soato',
        'region_name',
        'district_name',
        'management_message_id',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'management_message_id' => 'integer',
    ];

    // Relationships
    public function channelPosts(): HasMany
    {
        return $this->hasMany(ChannelPost::class);
    }

    public function actionLogs(): HasMany
    {
        return $this->hasMany(ActionLog::class);
    }

    public function osonIshVacancy(): BelongsTo
    {
        return $this->belongsTo(OsonIshVacancy::class, 'oson_ish_vacancy_id', 'oson_ish_vacancy_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }

    public function scopeByRegion($query, $regionSoato)
    {
        return $query->where('region_soato', $regionSoato);
    }

    // Status helpers
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function approve(): void
    {
        $this->update(['status' => 'approved']);
    }

    public function reject(): void
    {
        $this->update(['status' => 'rejected']);
    }

    public function markAsPublished(): void
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    // Helpers
    public function getTotalClicksAttribute(): int
    {
        return $this->channelPosts()->sum('clicks_count');
    }
}
