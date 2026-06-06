<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NeighborhoodReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'criterion'    => $this->criterion,
            'score'        => $this->score,
            'latitude'     => (float) $this->latitude,
            'longitude'    => (float) $this->longitude,
            'city'         => $this->city,
            'neighborhood' => $this->neighborhood,
            'comment'      => $this->comment,
            'is_validated' => $this->is_validated,
            'created_at'   => $this->created_at->toIso8601String(),

            'user' => $this->whenLoaded('user', fn () => [
                'id'             => $this->user->id,
                'name'           => $this->user->name,
                'avatar_thumb_url' => $this->user->avatar_thumb_url,
            ]),
        ];
    }
}
