<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SavedSearchResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'name'                  => $this->name,
            'criteria'              => $this->criteria,
            'notifications_enabled' => $this->notifications_enabled,
            'last_notified_at'      => $this->last_notified_at?->toIso8601String(),
            'created_at'            => $this->created_at->toIso8601String(),
        ];
    }
}
