<?php

namespace App\Http\Resources\Property;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyMapResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $primary = $this->relationLoaded('primaryImage')
            ? $this->primaryImage
            : ($this->relationLoaded('images')
                ? ($this->images->firstWhere('is_primary', true) ?? $this->images->first())
                : null);

        return [
            'id'            => $this->id,
            'latitude'      => $this->latitude,
            'longitude'     => $this->longitude,
            'price'         => $this->price,
            'type'          => $this->type,
            'status'        => $this->status,
            'thumbnail_url' => $primary?->thumbnail_url,
        ];
    }
}
