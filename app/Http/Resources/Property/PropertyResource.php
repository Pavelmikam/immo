<?php

namespace App\Http\Resources\Property;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'title'            => $this->title,
            'description'      => $this->description,
            'type'             => $this->type,
            'transaction_type' => $this->transaction_type,
            'price'            => $this->price,
            'surface'            => $this->surface,
            'rooms'              => $this->rooms,
            'bathrooms'          => $this->bathrooms,
            'floor'              => $this->floor,
            'deposit_amount'     => $this->deposit_amount,
            'min_rental_months'  => $this->min_rental_months,
            'address'            => $this->address,
            'city'             => $this->city,
            'district'         => $this->district,
            'latitude'         => $this->latitude,
            'longitude'        => $this->longitude,
            'status'           => $this->status,
            'rejection_reason' => $this->when($this->rejection_reason !== null, $this->rejection_reason),
            'published_at'     => $this->published_at?->toIso8601String(),
            'is_featured'      => $this->is_featured,
            'amenities'          => $this->amenities ?? [],
            'charges_included'   => $this->charges_included ?? [],
            'accepts_animals'    => $this->accepts_animals,
            'accepts_smokers'    => $this->accepts_smokers,
            'accepts_students'   => $this->accepts_students,
            'available_from'     => $this->available_from?->toDateString(),
            'favorites_count'  => $this->favorites_count,
            'views_count'      => $this->views_count,
            'is_favorited'     => (function () {
                $user = auth()->guard('sanctum')->user();
                return $user ? $user->hasFavorited($this->id) : false;
            })(),
            'images'             => PropertyImageResource::collection($this->whenLoaded('images')),
            'owner'              => new UserResource($this->whenLoaded('owner')),
            'neighborhood_score' => $this->when(
                $this->latitude && $this->longitude,
                fn () => $this->getNeighborhoodScore()
            ),
            'created_at'       => $this->created_at->toIso8601String(),
            'updated_at'       => $this->updated_at->toIso8601String(),
        ];
    }
}
