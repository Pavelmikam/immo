<?php

namespace App\Http\Resources\Property;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyListResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        // Support both eager-loaded primaryImage and images relations
        $primary = $this->relationLoaded('images')
            ? ($this->images->firstWhere('is_primary', true) ?? $this->images->first())
            : ($this->relationLoaded('primaryImage') ? $this->primaryImage : null);

        $user = auth()->guard('sanctum')->user();

        return [
            'id'               => $this->id,
            'title'            => $this->title,
            'type'             => $this->type,
            'transaction_type' => $this->transaction_type,
            'price'            => $this->price,
            'surface'          => $this->surface,
            'rooms'            => $this->rooms,
            'city'             => $this->city,
            'district'         => $this->district,
            'neighborhood'     => $this->district,
            'status'           => $this->status,
            'rejection_reason' => $this->when($this->rejection_reason !== null, $this->rejection_reason),
            'is_featured'      => $this->is_featured,
            'thumbnail_url'    => $primary?->thumbnail_url,
            'published_at'     => $this->published_at?->toIso8601String(),
            'views_count'      => $this->views_count ?? 0,
            'requests_count'   => $this->requests_count ?? 0,
            'favorites_count'  => $this->favorites_count,
            'is_favorited'     => $user ? $user->hasFavorited($this->id) : false,
            'owner'            => $this->whenLoaded('owner', fn () => [
                'id'    => $this->owner->id,
                'name'  => $this->owner->name,
                'email' => $this->owner->email,
            ]),
            'neighborhood_global_score' => $this->when(
                $this->latitude && $this->longitude,
                fn () => optional($this->getNeighborhoodScore())['global_score'] ?? null
            ),
        ];
    }
}
