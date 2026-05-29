<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use HasFactory;

    // ⚠️ IMMUABLE : les messages ne peuvent pas être modifiés ni supprimés

    protected $fillable = [
        'conversation_id', 'sender_id', 'body', 'type',
    ];

    protected $casts = [];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isSentBy(User $user): bool
    {
        return $this->sender_id === $user->id;
    }

    public function isSystemMessage(): bool
    {
        return $this->type === 'system';
    }
}
