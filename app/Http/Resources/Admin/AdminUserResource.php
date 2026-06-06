<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'name'                  => $this->name,
            'email'                 => $this->email,
            'role'                  => $this->role,
            'phone'                 => $this->phone,
            'city'                  => $this->city,
            'bio'                   => $this->bio,
            'avatar_url'            => $this->avatar_url,
            'avatar_thumb_url'      => $this->avatar_thumb_url,
            'is_active'             => $this->is_active,
            'email_verified_at'     => $this->email_verified_at?->toIso8601String(),
            'created_at'            => $this->created_at->toIso8601String(),
            'updated_at'            => $this->updated_at->toIso8601String(),
            'deleted_at'            => $this->deleted_at?->toIso8601String(),

            'properties_count'      => $this->whenCounted('properties', fn () => $this->properties_count, fn () => $this->properties()->count()),
            'rental_requests_count' => $this->whenCounted('rentalRequests', fn () => $this->rental_requests_count, fn () => $this->rentalRequests()->count()),
            'reports_count'         => $this->whenCounted('reportsSubmitted', fn () => $this->reports_submitted_count, fn () => $this->reportsSubmitted()->count()),

            'is_suspended'          => !$this->is_active,
            'is_verified'           => $this->isEmailVerified(),
        ];
    }
}
