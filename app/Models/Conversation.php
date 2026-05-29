<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id', 'initiated_by', 'rental_request_id',
        'subject', 'last_message_preview', 'last_message_at',
        'last_message_by', 'is_archived',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'is_archived'     => 'boolean',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function rentalRequest(): BelongsTo
    {
        return $this->belongsTo(RentalRequest::class);
    }

    public function lastMessageByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_message_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
                    ->withPivot([
                        'unread_count', 'last_read_at',
                        'is_archived', 'left_at',
                    ])
                    ->withTimestamps();
    }

    public function attachments(): HasManyThrough
    {
        return $this->hasManyThrough(
            MessageAttachment::class,
            Message::class,
            'conversation_id',
            'message_id',
        );
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->whereHas('participants', function (Builder $q) use ($userId) {
            $q->where('user_id', $userId)
              ->whereNull('left_at');
        });
    }

    public function scopeNotArchivedForUser(Builder $query, int $userId): Builder
    {
        return $query->whereHas('participants', function (Builder $q) use ($userId) {
            $q->where('user_id', $userId)
              ->where('is_archived', false)
              ->whereNull('left_at');
        });
    }

    public function scopeWithUnread(Builder $query, int $userId): Builder
    {
        return $query->whereHas('participants', function (Builder $q) use ($userId) {
            $q->where('user_id', $userId)
              ->where('unread_count', '>', 0);
        });
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isParticipant(User $user): bool
    {
        if ($this->relationLoaded('participants')) {
            return $this->participants->contains('id', $user->id);
        }
        return $this->participants()->where('user_id', $user->id)->exists();
    }

    public function getUnreadCountFor(User $user): int
    {
        if ($this->relationLoaded('participants')) {
            $pivot = $this->participants
                          ->firstWhere('id', $user->id)
                          ?->pivot;
            return $pivot?->unread_count ?? 0;
        }
        return (int) $this->participants()
                          ->where('user_id', $user->id)
                          ->value('unread_count');
    }
}
