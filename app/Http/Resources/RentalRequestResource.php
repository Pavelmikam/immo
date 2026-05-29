<?php

namespace App\Http\Resources;

use App\Http\Resources\Property\PropertyListResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RentalRequestResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'status'              => $this->status,
            'message'             => $this->message,
            'owner_response'      => $this->owner_response,
            'decided_at'          => $this->decided_at,
            'visit_scheduled_at'  => $this->visit_scheduled_at,
            'visit_confirmed'     => $this->visit_confirmed,
            'dossier_complete'    => $this->dossier_complete,
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,

            'property'        => PropertyListResource::make($this->whenLoaded('property')),
            'tenant'          => UserResource::make($this->whenLoaded('tenant')),
            'documents'       => RentalDocumentResource::collection($this->whenLoaded('documents')),
            'documents_count' => $this->documents_count ?? $this->documents()->count(),
        ];
    }
}
