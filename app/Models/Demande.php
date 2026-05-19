<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Demande extends Model
{
    protected $fillable = [
        'nom',
        'email',
        'telephone',
        'message',
        'bien_id',
        'user_id',
        'statut',
    ];

    public function bien(): BelongsTo
    {
        return $this->belongsTo(Bien::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
