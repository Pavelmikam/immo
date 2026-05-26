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
            'status'           => $this->status,
            'is_featured'      => $this->is_featured,
            'thumbnail_url'    => $primary?->thumbnail_url,
            'published_at'     => $this->published_at?->toIso8601String(),
            'favorites_count'  => $this->favorites_count,
            'is_favorited'     => $user ? $user->hasFavorited($this->id) : false,
        ];
    }
}
