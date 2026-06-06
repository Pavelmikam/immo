<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NeighborhoodScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'city', 'neighborhood',
        'center_latitude', 'center_longitude',
        'criterion', 'average_score', 'global_score',
        'report_count', 'unique_reporters',
        'period_start', 'period_end', 'computed_at',
    ];

    protected $casts = [
        'average_score'   => 'decimal:2',
        'global_score'    => 'decimal:2',
        'center_latitude' => 'decimal:7',
        'center_longitude'=> 'decimal:7',
        'period_start'    => 'date',
        'period_end'      => 'date',
        'computed_at'     => 'datetime',
    ];

    public function scopeByCity(Builder $query, string $city): Builder
    {
        return $query->where('city', $city);
    }

    public function scopeByNeighborhood(Builder $query, string $neighborhood): Builder
    {
        return $query->where('neighborhood', $neighborhood);
    }

    public function scopeByCriterion(Builder $query, string $criterion): Builder
    {
        return $query->where('criterion', $criterion);
    }

    public function getScoreLabel(): string
    {
        return match (true) {
            $this->average_score >= 4.5 => 'Excellent',
            $this->average_score >= 3.5 => 'Bien',
            $this->average_score >= 2.5 => 'Moyen',
            $this->average_score >= 1.5 => 'Mauvais',
            default                     => 'Très mauvais',
        };
    }

    public function getScoreColor(): string
    {
        return match (true) {
            $this->average_score >= 4.0 => 'green',
            $this->average_score >= 3.0 => 'yellow',
            $this->average_score >= 2.0 => 'orange',
            default                     => 'red',
        };
    }
}
