<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageAttachmentResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'original_name'   => $this->original_name,
            'mime_type'       => $this->mime_type,
            'file_size'       => $this->file_size,
            'attachment_type' => $this->attachment_type,
            'url'             => $this->url,
            'created_at'      => $this->created_at,
        ];
    }
}
