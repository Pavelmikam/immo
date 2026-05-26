<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'email'             => $this->email,
            'role'              => $this->role,
            'phone'             => $this->phone,
            'city'              => $this->city,
            'bio'               => $this->bio,
            'avatar_url'        => $this->avatar_url,
            'avatar_thumb_url'  => $this->avatar_thumb_url,
            'is_active'         => $this->is_active,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'created_at'        => $this->created_at?->toIso8601String(),
        ];
    }
}
