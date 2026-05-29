<?php

namespace App\Contracts;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Property;
use App\Models\User;

interface MessagingServiceInterface
{
    public function findOrCreateConversation(
        User $initiator,
        Property $property,
        ?int $rentalRequestId = null
    ): Conversation;

    public function sendMessage(
        Conversation $conversation,
        User $sender,
        string $body,
        array $attachmentFiles = []
    ): Message;

    public function markAsRead(Conversation $conversation, User $user): void;

    public function archiveForUser(Conversation $conversation, User $user): void;

    public function unarchiveForUser(Conversation $conversation, User $user): void;

    public function getTotalUnread(User $user): int;
}
