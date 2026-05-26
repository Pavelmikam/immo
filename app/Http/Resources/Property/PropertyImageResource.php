<?php

namespace App\Http\Resources\Property;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyImageResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'original_url'  => $this->original_url,
            'optimized_url' => $this->optimized_url,
            'thumbnail_url' => $this->thumbnail_url,
            'order'         => $this->order,
            'is_primary'    => $this->is_primary,
            'caption'       => $this->caption,
        ];
    }
}
