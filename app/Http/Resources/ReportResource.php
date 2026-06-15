<?php

namespace App\Http\Resources;

use App\Models\Message;
use App\Models\Property;
use App\Http\Resources\Property\PropertyListResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'reason'      => $this->reason,
            'description' => $this->description,
            'status'      => $this->status,
            'admin_note'  => $this->admin_note,
            'handled_at'  => $this->handled_at?->toIso8601String(),
            'created_at'  => $this->created_at->toIso8601String(),

            'reporter'    => $this->whenLoaded('reporter', fn () => [
                'id'             => $this->reporter->id,
                'name'           => $this->reporter->name,
                'avatar_thumb_url' => $this->reporter->avatar_thumb_url,
            ]),

            'reportable'  => $this->whenLoaded('reportable', function () {
                if (!$this->reportable) {
                    return null;
                }
                return match (true) {
                    $this->reportable instanceof Property => new PropertyListResource($this->reportable),
                    $this->reportable instanceof Message  => new MessageResource($this->reportable),
                    default                               => ['id' => $this->reportable->id],
                };
            }),

            'handled_by'  => $this->whenLoaded('handledByAdmin', fn () => $this->handledByAdmin ? [
                'id'   => $this->handledByAdmin->id,
                'name' => $this->handledByAdmin->name,
            ] : null),
        ];
    }
}
