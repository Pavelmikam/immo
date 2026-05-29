<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'body'       => $this->body,
            'type'       => $this->type,
            'created_at' => $this->created_at,
            'is_mine'    => $request->user()?->id === $this->sender_id,

            'sender' => $this->whenLoaded('sender', fn () => [
                'id'               => $this->sender->id,
                'name'             => $this->sender->name,
                'avatar_thumb_url' => $this->sender->avatar_thumb_url,
                'role'             => $this->sender->role,
            ]),

            'attachments' => MessageAttachmentResource::collection(
                $this->whenLoaded('attachments')
            ),
        ];
    }
}
