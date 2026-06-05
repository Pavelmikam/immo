<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'phone', 'city',
        'bio', 'avatar_path', 'avatar_thumb_path', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $appends = ['avatar_url', 'avatar_thumb_url'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active'         => 'boolean',
            'password'          => 'hashed',
        ];
    }

    public function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->avatar_path
                ? Storage::disk(config('app.avatar_disk', 'public'))->url($this->avatar_path)
                : null
        );
    }

    public function avatarThumbUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->avatar_thumb_path
                ? Storage::disk(config('app.avatar_disk', 'public'))->url($this->avatar_thumb_path)
                : null
        );
    }

    // ── Role helpers ──────────────────────────────────────────────────────────

    public function isAdmin(): bool        { return $this->role === 'admin'; }
    public function isProprietaire(): bool { return $this->role === 'proprietaire'; }
    public function isLocataire(): bool    { return $this->role === 'locataire'; }
    public function isSuspended(): bool    { return $this->is_active === false; }
    public function isEmailVerified(): bool{ return $this->email_verified_at !== null; }

    // ── Relations ─────────────────────────────────────────────────────────────

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'favorites')
                    ->withTimestamps()
                    ->orderByPivot('created_at', 'desc');
    }

    public function savedSearches(): HasMany
    {
        return $this->hasMany(SavedSearch::class);
    }

    public function rentalRequests(): HasMany
    {
        return $this->hasMany(RentalRequest::class, 'tenant_id');
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(
            Conversation::class,
            'conversation_participants'
        )->withPivot([
            'unread_count', 'last_read_at', 'is_archived', 'left_at',
        ])->withTimestamps()
          ->orderByPivot('updated_at', 'desc');
    }

    public function getTotalUnreadCount(): int
    {
        return (int) $this->conversations()
                          ->wherePivot('left_at', null)
                          ->sum('conversation_participants.unread_count');
    }

    public function notificationPreference(): HasOne
    {
        return $this->hasOne(NotificationPreference::class);
    }

    public function getOrCreateNotificationPreferences(): NotificationPreference
    {
        return $this->notificationPreference()
                    ->firstOrCreate([], [
                        'channels'      => ['mail' => true, 'database' => true],
                        'enabled_types' => [],
                    ]);
    }

    public function getUnreadNotificationsCount(): int
    {
        return $this->unreadNotifications()->count();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function hasActiveDemandFor(int $propertyId): bool
    {
        return $this->rentalRequests()
                    ->where('property_id', $propertyId)
                    ->whereIn('status', ['en_attente', 'acceptee'])
                    ->exists();
    }

    public function hasFavorited(int $propertyId): bool
    {
        if ($this->relationLoaded('favorites')) {
            return $this->favorites->contains('id', $propertyId);
        }
        return $this->favorites()->where('property_id', $propertyId)->exists();
    }
}
