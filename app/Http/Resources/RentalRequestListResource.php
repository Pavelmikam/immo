<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RentalRequestListResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'status'           => $this->status,
            'message'          => $this->message ? mb_substr($this->message, 0, 100) : null,
            'decided_at'       => $this->decided_at,
            'dossier_complete' => $this->dossier_complete,
            'created_at'       => $this->created_at,

            'tenant' => $this->whenLoaded('tenant', fn () => [
                'id'              => $this->tenant->id,
                'name'            => $this->tenant->name,
                'avatar_thumb_url'=> $this->tenant->avatar_thumb_url,
            ]),

            'property' => $this->whenLoaded('property', fn () => [
                'id'            => $this->property->id,
                'title'         => $this->property->title,
                'city'          => $this->property->city,
                'price'         => $this->property->price,
                'thumbnail_url' => $this->property->relationLoaded('primaryImage')
                    ? $this->property->primaryImage?->thumbnail_url
                    : null,
            ]),
        ];
    }
}
