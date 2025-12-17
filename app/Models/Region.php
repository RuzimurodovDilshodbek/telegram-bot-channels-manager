<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_uz',
        'name_ru',
        'name_en',
        'name_cyrl',
        'c_order',
        'ns10_code',
        'soato',
        'living_region_id',
        'name_kar',
        'online_makhalla_obl_id',
    ];

    protected $casts = [
        'c_order' => 'integer',
        'ns10_code' => 'integer',
        'soato' => 'integer',
        'living_region_id' => 'integer',
        'online_makhalla_obl_id' => 'integer',
    ];

    // Relationship with channels
    public function channels()
    {
        return $this->hasMany(Channel::class);
    }

    // Helper method to get display name
    public function getDisplayNameAttribute()
    {
        return $this->name_uz;
    }
}
