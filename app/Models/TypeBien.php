<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeBien extends Model
{
    protected $fillable = ['nom', 'description'];

    public function biens(): HasMany
    {
        return $this->hasMany(Bien::class);
    }
}
