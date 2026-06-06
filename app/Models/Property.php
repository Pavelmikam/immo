<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
// NeighborhoodScore is referenced in getNeighborhoodScore()
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use HasFactory, SoftDeletes;

    protected $attributes = [
        'status'      => 'draft',
        'is_featured' => false,
    ];

    protected $fillable = [
        'user_id', 'title', 'description', 'type', 'transaction_type',
        'price', 'surface', 'rooms', 'bathrooms', 'address', 'city',
        'district', 'latitude', 'longitude', 'status', 'rejection_reason',
        'published_at', 'is_featured', 'amenities', 'available_from',
        'views_count', 'favorites_count', 'requests_count',
    ];

    protected $casts = [
        'price'          => 'integer',
        'surface'        => 'integer',
        'rooms'          => 'integer',
        'bathrooms'      => 'integer',
        'latitude'       => 'float',
        'longitude'      => 'float',
        'is_featured'    => 'boolean',
        'published_at'   => 'datetime',
        'amenities'      => 'array',
        'available_from' => 'date',
        'views_count'    => 'integer',
        'favorites_count'=> 'integer',
        'requests_count' => 'integer',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class)->orderBy('order');
    }

    /** Primary image eager-loadable relation: primary first, then by order */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(PropertyImage::class)
                    ->orderByRaw('is_primary DESC, `order` ASC');
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    public function rentalRequests(): HasMany
    {
        return $this->hasMany(RentalRequest::class);
    }

    public function propertyViews(): HasMany
    {
        return $this->hasMany(PropertyView::class);
    }

    public function recordView(
        ?int $userId,
        ?string $sessionId,
        ?string $ip = null,
        ?string $userAgent = null,
        ?string $referrer = null
    ): bool {
        $alreadyViewed = PropertyView::where('property_id', $this->id)
            ->where(function ($q) use ($userId, $sessionId) {
                if ($userId) {
                    $q->where('user_id', $userId);
                } elseif ($sessionId) {
                    $q->where('session_id', $sessionId);
                } else {
                    $q->whereRaw('1=0');
                }
            })
            ->where('viewed_at', '>=', now()->subMinutes(30))
            ->exists();

        if ($alreadyViewed) {
            return false;
        }

        PropertyView::create([
            'property_id' => $this->id,
            'user_id'     => $userId,
            'session_id'  => $sessionId,
            'ip_address'  => $ip,
            'user_agent'  => $userAgent,
            'referrer'    => $referrer,
            'viewed_at'   => now(),
        ]);

        $this->incrementViews();

        return true;
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function getNeighborhoodScore(): ?array
    {
        if (!$this->latitude || !$this->longitude) {
            return null;
        }

        $scores = NeighborhoodScore::byCity($this->city)
                                   ->when($this->district,
                                       fn ($q) => $q->byNeighborhood($this->district)
                                   )
                                   ->get();

        if ($scores->isEmpty()) {
            return null;
        }

        $byCriterion = $scores->keyBy('criterion');

        return [
            'city'         => $this->city,
            'neighborhood' => $this->district,
            'global_score' => $scores->first()?->global_score,
            'criteria'     => $byCriterion->map(fn (NeighborhoodScore $s) => [
                'score'        => (float) $s->average_score,
                'label'        => $s->getScoreLabel(),
                'color'        => $s->getScoreColor(),
                'report_count' => $s->report_count,
            ])->toArray(),
            'computed_at'  => $scores->first()?->computed_at?->toIso8601String(),
        ];
    }

    // ── Status scopes ──────────────────────────────────────────────────────────

    /** Publicly visible: only active properties */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeForRent(Builder $query): Builder
    {
        return $query->where('transaction_type', 'rent');
    }

    public function scopeForSale(Builder $query): Builder
    {
        return $query->where('transaction_type', 'sale');
    }

    // ── Filter scopes ──────────────────────────────────────────────────────────

    public function scopeByOwner(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByCity(Builder $query, string $city): Builder
    {
        return $query->where('city', 'like', '%' . $city . '%');
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeByNeighborhood(Builder $query, string $neighborhood): Builder
    {
        return $query->where('district', 'like', '%' . $neighborhood . '%');
    }

    public function scopePriceBetween(Builder $query, ?float $min, ?float $max): Builder
    {
        if ($min !== null) {
            $query->where('price', '>=', $min);
        }
        if ($max !== null) {
            $query->where('price', '<=', $max);
        }
        return $query;
    }

    public function scopeSurfaceBetween(Builder $query, ?float $min, ?float $max): Builder
    {
        if ($min !== null) {
            $query->where('surface', '>=', $min);
        }
        if ($max !== null) {
            $query->where('surface', '<=', $max);
        }
        return $query;
    }

    public function scopeWithRooms(Builder $query, int $min): Builder
    {
        return $query->where('rooms', '>=', $min);
    }

    public function scopeAvailableFrom(Builder $query, string $date): Builder
    {
        return $query->where(function (Builder $q) use ($date) {
            $q->whereNull('available_from')
              ->orWhere('available_from', '<=', $date);
        });
    }

    /** AND logic: property must contain ALL listed amenities */
    public function scopeHasAmenities(Builder $query, array $amenities): Builder
    {
        foreach ($amenities as $amenity) {
            $query->whereJsonContains('amenities', $amenity);
        }
        return $query;
    }

    /** Haversine distance filter — works on MySQL 8 and SQLite 3.35+ */
    public function scopeNearby(Builder $query, float $lat, float $lng, float $radiusKm = 5.0): Builder
    {
        $latRad = deg2rad($lat);
        $lngRad = deg2rad($lng);

        return $query->whereNotNull('latitude')
                     ->whereNotNull('longitude')
                     ->whereRaw(
                         '(6371 * acos(
                             cos(?) * cos(radians(latitude)) *
                             cos(radians(longitude) - ?) +
                             sin(?) * sin(radians(latitude))
                         )) <= ?',
                         [$latRad, $lngRad, $latRad, $radiusKm]
                     );
    }

    public function scopeSortBy(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            'price_asc'  => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'oldest'     => $query->orderBy('created_at', 'asc'),
            'popular'    => $query->orderBy('views_count', 'desc'),
            'relevance'  => $query->orderBy('favorites_count', 'desc')->orderBy('views_count', 'desc'),
            default      => $query->orderBy('created_at', 'desc'), // newest
        };
    }

    // ── Counter helpers ───────────────────────────────────────────────────────

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    public function incrementFavorites(): void
    {
        $this->increment('favorites_count');
    }

    public function decrementFavorites(): void
    {
        $this->decrement('favorites_count');
    }

    public function incrementRequests(): void
    {
        $this->increment('requests_count');
    }

    // ── Status helpers ────────────────────────────────────────────────────────

    public function isOwner(User $user): bool    { return $this->user_id === $user->id; }
    public function isOwnedBy(User $user): bool  { return $this->user_id === $user->id; }
    public function isAvailable(): bool          { return $this->status === 'active'; }
    public function isDraft(): bool              { return $this->status === 'draft'; }
    public function isPending(): bool            { return $this->status === 'pending'; }
    public function isActive(): bool             { return $this->status === 'active'; }
    public function isRejected(): bool           { return $this->status === 'rejected'; }
    public function isArchived(): bool           { return $this->status === 'archived'; }
    public function isSousReservation(): bool    { return $this->status === 'sous_reservation'; }
}
