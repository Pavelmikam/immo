<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = ['client_id', 'agent_id', 'bien_id', 'statut'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function bien(): BelongsTo
    {
        return $this->belongsTo(Bien::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function dernierMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function messagesNonLus(): HasMany
    {
        return $this->hasMany(Message::class)->where('lu', false);
    }
}
