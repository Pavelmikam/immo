<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NeighborhoodReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'criterion', 'score',
        'latitude', 'longitude',
        'city', 'neighborhood', 'comment',
        'is_validated', 'is_flagged',
    ];

    protected $casts = [
        'score'        => 'integer',
        'latitude'     => 'decimal:7',
        'longitude'    => 'decimal:7',
        'is_validated' => 'boolean',
        'is_flagged'   => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeValidated(Builder $query): Builder
    {
        return $query->where('is_validated', true);
    }

    public function scopeByCriterion(Builder $query, string $criterion): Builder
    {
        return $query->where('criterion', $criterion);
    }

    public function scopeByCity(Builder $query, string $city): Builder
    {
        return $query->where('city', 'LIKE', "%{$city}%");
    }

    public function scopeRecent(Builder $query, int $months = 3): Builder
    {
        return $query->where('created_at', '>=', now()->subMonths($months));
    }

    public function scopeNearLocation(
        Builder $query,
        float $lat,
        float $lng,
        float $radiusKm = 2.0
    ): Builder {
        $latRad = deg2rad($lat);
        return $query->whereRaw(
            '(6371 * acos(LEAST(1.0, GREATEST(-1.0,
                cos(?) * cos(radians(latitude)) *
                cos(radians(longitude) - ?) +
                sin(?) * sin(radians(latitude))
            )))) <= ?',
            [$latRad, deg2rad($lng), $latRad, $radiusKm]
        );
    }

    public function scopeNotFlagged(Builder $query): Builder
    {
        return $query->where('is_flagged', false);
    }

    public function isGoodScore(): bool
    {
        return $this->score >= 4;
    }

    public function isBadScore(): bool
    {
        return $this->score <= 2;
    }
}
