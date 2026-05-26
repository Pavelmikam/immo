<?php

namespace App\Services;

use App\Contracts\PropertyFilterServiceInterface;
use App\Models\Property;
use Illuminate\Database\Eloquent\Builder;

class PropertyFilterService implements PropertyFilterServiceInterface
{
    public function buildQuery(array $filters): Builder
    {
        $query = Property::with(['images', 'owner'])->public();

        if (!empty($filters['city'])) {
            $query->byCity($filters['city']);
        }
        if (!empty($filters['type'])) {
            $query->byType($filters['type']);
        }
        if (!empty($filters['transaction_type'])) {
            $query->where('transaction_type', $filters['transaction_type']);
        }
        if (!empty($filters['neighborhood'])) {
            $query->byNeighborhood($filters['neighborhood']);
        }
        if (isset($filters['price_min']) || isset($filters['price_max'])) {
            $query->priceBetween(
                isset($filters['price_min']) ? (float) $filters['price_min'] : null,
                isset($filters['price_max']) ? (float) $filters['price_max'] : null,
            );
        }
        if (isset($filters['surface_min']) || isset($filters['surface_max'])) {
            $query->surfaceBetween(
                isset($filters['surface_min']) ? (float) $filters['surface_min'] : null,
                isset($filters['surface_max']) ? (float) $filters['surface_max'] : null,
            );
        }
        if (!empty($filters['rooms_min'])) {
            $query->withRooms((int) $filters['rooms_min']);
        }
        if (!empty($filters['amenities']) && is_array($filters['amenities'])) {
            $query->hasAmenities($filters['amenities']);
        }
        if (!empty($filters['available_from'])) {
            $query->availableFrom($filters['available_from']);
        }
        if (
            isset($filters['latitude'], $filters['longitude'])
            && is_numeric($filters['latitude'])
            && is_numeric($filters['longitude'])
        ) {
            $radius = isset($filters['radius_km'])
                ? min((float) $filters['radius_km'], 50.0)
                : 5.0;
            $query->nearby((float) $filters['latitude'], (float) $filters['longitude'], $radius);
        }

        // Featured properties always float to the top
        $query->orderByDesc('is_featured');

        $sort = $filters['sort'] ?? 'newest';
        $query->sortBy($sort);

        return $query;
    }

    public function allowedFilters(): array
    {
        return [
            'city'             => 'string',
            'type'             => 'string',
            'transaction_type' => 'string',
            'neighborhood'     => 'string',
            'price_min'      => 'numeric',
            'price_max'      => 'numeric',
            'surface_min'    => 'numeric',
            'surface_max'    => 'numeric',
            'rooms_min'      => 'integer',
            'amenities'      => 'array',
            'available_from' => 'date',
            'latitude'       => 'numeric',
            'longitude'      => 'numeric',
            'radius_km'      => 'numeric',
            'sort'           => 'string',
        ];
    }
}
