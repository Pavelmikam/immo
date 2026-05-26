<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bien extends Model
{
    use HasFactory;

    protected $fillable = [
        'titre',
        'description',
        'prix',
        'surface',
        'nb_pieces',
        'nb_chambres',
        'nb_salles_bain',
        'adresse',
        'quartier',
        'ville',
        'code_postal',
        'statut',
        'disponible',
        'type_bien_id',
        'user_id',
        'agent_id',
        'ville_id',
    ];

    protected $casts = [
        'disponible'      => 'boolean',
        'prix'            => 'decimal:2',
        'surface'         => 'decimal:2',
        'nb_pieces'       => 'integer',
        'nb_chambres'     => 'integer',
        'nb_salles_bain'  => 'integer',
    ];

    public function typeBien(): BelongsTo
    {
        return $this->belongsTo(TypeBien::class);
    }

    public function proprietaire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function ville(): BelongsTo
    {
        return $this->belongsTo(Ville::class);
    }

    public function demandes(): HasMany
    {
        return $this->hasMany(Demande::class);
    }

    public function favoris(): HasMany
    {
        return $this->hasMany(Favori::class);
    }
}
