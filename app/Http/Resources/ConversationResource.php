<?php

namespace App\Http\Resources;

use App\Http\Resources\Property\PropertyListResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $currentUserId = $request->user()?->id;

        return [
            'id'                   => $this->id,
            'subject'              => $this->subject,
            'last_message_preview' => $this->last_message_preview,
            'last_message_at'      => $this->last_message_at,
            'is_archived'          => $this->is_archived,
            'created_at'           => $this->created_at,

            'property' => PropertyListResource::make($this->whenLoaded('property')),

            'other_participant' => $this->whenLoaded('participants', function () use ($currentUserId) {
                $other = $this->participants->first(fn ($p) => $p->id !== $currentUserId);
                if (!$other) return null;
                return [
                    'id'               => $other->id,
                    'name'             => $other->name,
                    'avatar_thumb_url' => $other->avatar_thumb_url,
                    'role'             => $other->role,
                ];
            }),

            'unread_count' => $this->whenLoaded('participants', function () use ($currentUserId) {
                $me = $this->participants->firstWhere('id', $currentUserId);
                return $me?->pivot->unread_count ?? 0;
            }, 0),

            'messages_count' => $this->messages()->count(),

            'messages' => MessageResource::collection($this->whenLoaded('messages')),

            'rental_request' => $this->whenLoaded('rentalRequest', fn () => $this->rentalRequest
                ? ['id' => $this->rentalRequest->id, 'status' => $this->rentalRequest->status]
                : null
            ),
        ];
    }
}
