<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OsonIshVacancy extends Model
{
    use HasFactory;

    protected $fillable = [
        'oson_ish_vacancy_id',
        'company_tin',
        'company_name',
        'vacancy_status',
        'title',
        'count',
        'payment_name',
        'work_name',
        'min_salary',
        'max_salary',
        'work_experience_name',
        'age_from',
        'age_to',
        'gender',
        'for_whos_name',
        'phone',
        'hr_fio',
        'region_code',
        'region_name',
        'district_name',
        'description',
        'show_url',
        'previous_status',
        'status_changed_at',
    ];

    protected $casts = [
        'vacancy_status' => 'integer',
        'count' => 'integer',
        'min_salary' => 'decimal:2',
        'max_salary' => 'decimal:2',
        'age_from' => 'integer',
        'age_to' => 'integer',
        'gender' => 'integer',
        'previous_status' => 'integer',
        'status_changed_at' => 'datetime',
    ];

    // Relationships
    public function botVacancy(): HasOne
    {
        return $this->hasOne(BotVacancy::class, 'oson_ish_vacancy_id', 'oson_ish_vacancy_id');
    }

    // Status constants
    const STATUS_ACTIVE = 2;

    // Helpers
    public function isActive(): bool
    {
        return $this->vacancy_status === self::STATUS_ACTIVE;
    }

    public function wasActive(): bool
    {
        return $this->previous_status === self::STATUS_ACTIVE;
    }

    public function statusChanged(): bool
    {
        return $this->previous_status !== null && $this->previous_status !== $this->vacancy_status;
    }

    public function becameActive(): bool
    {
        return $this->statusChanged() && $this->isActive() && !$this->wasActive();
    }

    public function becameInactive(): bool
    {
        return $this->statusChanged() && !$this->isActive() && $this->wasActive();
    }

    public function getFormattedSalaryAttribute(): string
    {
        if ($this->min_salary && $this->max_salary) {
            return number_format($this->min_salary, 0, ',', ' ') . ' - ' . number_format($this->max_salary, 0, ',', ' ') . ' so\'m';
        }

        if ($this->min_salary) {
            return number_format($this->min_salary, 0, ',', ' ') . ' so\'mdan';
        }

        if ($this->max_salary) {
            return number_format($this->max_salary, 0, ',', ' ') . ' so\'mgacha';
        }

        return 'Kelishiladi';
    }

    public function getFormattedGenderAttribute(): string
    {
        return match($this->gender) {
            1 => 'Erkak',
            2 => 'Ayol',
            3 => 'Ahamiyatsiz',
            default => 'Ahamiyatsiz',
        };
    }

    public function getFormattedAgeAttribute(): ?string
    {
        if ($this->age_from && $this->age_to) {
            return "{$this->age_from} - {$this->age_to} yosh";
        }

        if ($this->age_from) {
            return "{$this->age_from} yoshdan";
        }

        if ($this->age_to) {
            return "{$this->age_to} yoshgacha";
        }

        return null;
    }
}
