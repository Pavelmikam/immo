<?php

namespace Tests\Traits;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Property;
use App\Models\User;

trait CreatesConversations
{
    protected function createApprovedProperty(?User $owner = null, array $attrs = []): Property
    {
        $owner = $owner ?? $this->makeProprietaire();
        return Property::factory()->for($owner, 'owner')->active()->create($attrs);
    }

    protected function createConversation(
        ?User $tenant = null,
        ?Property $property = null,
        array $attrs = []
    ): Conversation {
        $tenant   = $tenant ?? User::factory()->create([
            'role'              => 'locataire',
            'email_verified_at' => now(),
            'is_active'         => true,
        ]);
        $property = $property ?? $this->createApprovedProperty();

        $conversation = Conversation::factory()->create(array_merge([
            'property_id'  => $property->id,
            'initiated_by' => $tenant->id,
        ], $attrs));

        $conversation->participants()->attach($tenant->id, [
            'unread_count' => 0,
            'last_read_at' => now(),
        ]);
        $conversation->participants()->attach($property->user_id, [
            'unread_count' => 0,
            'last_read_at' => now(),
        ]);

        return $conversation->load('participants');
    }

    protected function createConversationWithMessages(
        ?User $tenant = null,
        int $messageCount = 3
    ): Conversation {
        $conversation = $this->createConversation($tenant);
        $property     = $conversation->property;
        $owner        = $property->owner;

        for ($i = 0; $i < $messageCount; $i++) {
            $sender = $i % 2 === 0
                ? $conversation->initiator
                : $owner;

            Message::factory()->create([
                'conversation_id' => $conversation->id,
                'sender_id'       => $sender->id,
            ]);
        }

        Conversation::withoutTimestamps(fn () =>
            $conversation->update(['last_message_at' => now()])
        );

        return $conversation->fresh()->load(['participants', 'messages']);
    }

    protected function setUnreadCount(
        Conversation $conversation,
        User $user,
        int $count
    ): void {
        $conversation->participants()->updateExistingPivot($user->id, [
            'unread_count' => $count,
        ]);
    }
}
