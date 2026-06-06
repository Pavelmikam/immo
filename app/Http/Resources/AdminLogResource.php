<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'action'     => $this->action,
            'before'     => $this->before,
            'after'      => $this->after,
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at->toIso8601String(),

            'admin'    => $this->whenLoaded('admin', fn () => [
                'id'   => $this->admin->id,
                'name' => $this->admin->name,
            ]),

            'loggable' => $this->loggable_type ? [
                'type' => $this->loggable_type,
                'id'   => $this->loggable_id,
            ] : null,
        ];
    }
}
