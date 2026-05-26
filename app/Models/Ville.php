<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ville extends Model
{
    protected $fillable = ['nom', 'code_postal', 'latitude', 'longitude', 'region_id'];

    protected $casts = [
        'latitude'  => 'float',
        'longitude' => 'float',
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function biens(): HasMany
    {
        return $this->hasMany(Bien::class);
    }
}
