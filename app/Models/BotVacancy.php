<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BotVacancy extends Model
{
    use HasFactory;

    protected $fillable = [
        'original_vacancy_id',
        'source',
        'status',
        'title',
        'company_name',
        'region_soato',
        'region_name',
        'district_soato',
        'district_name',
        'salary_min',
        'salary_max',
        'work_type',
        'busyness_type',
        'description',
        'show_url',
        'raw_data',
        'management_message_id',
        'published_at',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'published_at' => 'datetime',
        'salary_min' => 'integer',
        'salary_max' => 'integer',
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

    public function getFormattedSalaryAttribute(): string
    {
        if ($this->salary_min && $this->salary_max) {
            return number_format($this->salary_min) . ' - ' . number_format($this->salary_max) . ' UZS';
        } elseif ($this->salary_min) {
            return 'dan ' . number_format($this->salary_min) . ' UZS';
        } elseif ($this->salary_max) {
            return 'gacha ' . number_format($this->salary_max) . ' UZS';
        }
        return 'Kelishiladi';
    }
}
