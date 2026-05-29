<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\MessageAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageAttachmentFactory extends Factory
{
    protected $model = MessageAttachment::class;

    public function definition(): array
    {
        return [
            'message_id'      => Message::factory(),
            'file_path'       => 'messages/1/1/attachment_test.pdf',
            'original_name'   => 'document.pdf',
            'mime_type'       => 'application/pdf',
            'file_size'       => 204800,
            'attachment_type' => 'document',
        ];
    }
}
