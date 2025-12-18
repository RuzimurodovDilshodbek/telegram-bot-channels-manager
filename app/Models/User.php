<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'telegram_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Determine if the user can access the Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Allow all authenticated users to access the admin panel
        return true;
    }

    /**
     * Relationships
     */

    /**
     * Get all channel admin records for this user
     * (One user can be admin for multiple channels)
     */
    public function channelAdminRecords()
    {
        return $this->hasMany(ChannelAdmin::class, 'telegram_user_id', 'telegram_id');
    }

    /**
     * Helper Methods for Channel Admin
     */

    /**
     * Check if user is a channel admin (for any channel)
     */
    public function isChannelAdmin(): bool
    {
        if (!$this->telegram_id) {
            return false;
        }

        return $this->channelAdminRecords()->active()->exists();
    }

    /**
     * Check if user is admin for a specific channel
     */
    public function isAdminForChannel(int $channelId): bool
    {
        if (!$this->telegram_id) {
            return false;
        }

        return ChannelAdmin::isAdminForChannel($this->telegram_id, $channelId);
    }

    /**
     * Get all channels where this user is an active admin
     */
    public function getAdminChannels()
    {
        return $this->channelAdminRecords()->active()->with('channel')->get();
    }
}
