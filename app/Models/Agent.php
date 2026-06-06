<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'telephone',
        'specialite',
        'biographie',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function biens(): HasMany
    {
        return $this->hasMany(Bien::class);
    }

}
