<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationListResource extends JsonResource
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
            'created_at'           => $this->created_at,
            'is_archived'          => $this->is_archived,

            'unread_count' => $this->whenLoaded('participants', function () use ($currentUserId) {
                $me = $this->participants->firstWhere('id', $currentUserId);
                return $me?->pivot->unread_count ?? 0;
            }, 0),

            'property' => $this->whenLoaded('property', fn () => [
                'id'            => $this->property->id,
                'title'         => $this->property->title,
                'city'          => $this->property->city,
                'thumbnail_url' => $this->property->primaryImage?->thumbnail_url ?? null,
            ]),

            'other_participant' => $this->whenLoaded('participants', function () use ($currentUserId) {
                $other = $this->participants->first(fn ($p) => $p->id !== $currentUserId);
                if (!$other) return null;
                return [
                    'id'               => $other->id,
                    'name'             => $other->name,
                    'avatar_thumb_url' => $other->avatar_thumb_url,
                ];
            }),

            'last_message_by' => $this->whenLoaded('lastMessageByUser', fn () => $this->lastMessageByUser
                ? ['id' => $this->lastMessageByUser->id, 'name' => $this->lastMessageByUser->name]
                : null
            ),
        ];
    }
}
