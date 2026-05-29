<?php

namespace App\Services;

use App\Contracts\MessagingServiceInterface;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MessagingService implements MessagingServiceInterface
{
    public function findOrCreateConversation(
        User $initiator,
        Property $property,
        ?int $rentalRequestId = null
    ): Conversation {
        if (!$initiator->isLocataire()) {
            throw new \DomainException('Seul un locataire peut initier une conversation.');
        }

        if (!$property->isAvailable()) {
            throw new \DomainException('Impossible d\'ouvrir une conversation sur ce bien.');
        }

        if ($property->isOwnedBy($initiator)) {
            throw new \DomainException(
                'Vous ne pouvez pas initier une conversation sur votre propre bien.'
            );
        }

        return DB::transaction(function () use ($initiator, $property, $rentalRequestId) {
            $existing = Conversation::where('property_id', $property->id)
                                    ->where('initiated_by', $initiator->id)
                                    ->first();

            if ($existing) {
                return $existing;
            }

            $conversation = Conversation::create([
                'property_id'       => $property->id,
                'initiated_by'      => $initiator->id,
                'rental_request_id' => $rentalRequestId,
            ]);

            $conversation->participants()->attach($initiator->id, [
                'unread_count' => 0,
                'last_read_at' => now(),
            ]);

            $conversation->participants()->attach($property->user_id, [
                'unread_count' => 0,
                'last_read_at' => now(),
            ]);

            return $conversation;
        });
    }

    public function sendMessage(
        Conversation $conversation,
        User $sender,
        string $body,
        array $attachmentFiles = []
    ): Message {
        if (!$conversation->isParticipant($sender)) {
            throw new \DomainException('Vous n\'êtes pas participant de cette conversation.');
        }

        return DB::transaction(function () use ($conversation, $sender, $body, $attachmentFiles) {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id'       => $sender->id,
                'body'            => $body,
                'type'            => 'text',
            ]);

            foreach ($attachmentFiles as $file) {
                $this->storeAttachment($message, $file);
            }

            Conversation::withoutTimestamps(fn () =>
                $conversation->update([
                    'last_message_preview' => mb_substr($body, 0, 100),
                    'last_message_at'      => $message->created_at,
                    'last_message_by'      => $sender->id,
                ])
            );

            // Incrémenter unread_count pour tous les participants sauf le sender
            DB::table('conversation_participants')
              ->where('conversation_id', $conversation->id)
              ->where('user_id', '!=', $sender->id)
              ->whereNull('left_at')
              ->increment('unread_count');

            // Mettre à jour last_read_at du sender
            $conversation->participants()->updateExistingPivot($sender->id, [
                'last_read_at' => now(),
                'unread_count' => 0,
            ]);

            return $message->load('attachments', 'sender');
        });
    }

    public function markAsRead(Conversation $conversation, User $user): void
    {
        if (!$conversation->isParticipant($user)) {
            return;
        }

        $conversation->participants()->updateExistingPivot($user->id, [
            'unread_count' => 0,
            'last_read_at' => now(),
        ]);
    }

    public function archiveForUser(Conversation $conversation, User $user): void
    {
        if (!$conversation->isParticipant($user)) {
            throw new \DomainException('Action non autorisée.');
        }

        $conversation->participants()->updateExistingPivot($user->id, [
            'is_archived' => true,
        ]);
    }

    public function unarchiveForUser(Conversation $conversation, User $user): void
    {
        if (!$conversation->isParticipant($user)) {
            throw new \DomainException('Action non autorisée.');
        }

        $conversation->participants()->updateExistingPivot($user->id, [
            'is_archived' => false,
        ]);
    }

    public function getTotalUnread(User $user): int
    {
        return $user->getTotalUnreadCount();
    }

    private function storeAttachment(Message $message, UploadedFile $file): MessageAttachment
    {
        $disk      = config('filesystems.default_media_disk', 'media');
        $uid       = now()->timestamp . '_' . uniqid();
        $extension = $file->getClientOriginalExtension() ?: 'bin';
        $filePath  = "messages/{$message->conversation_id}/{$message->id}/{$uid}.{$extension}";

        Storage::disk($disk)->put(
            $filePath,
            file_get_contents($file->getRealPath())
        );

        $imageTypes     = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $attachmentType = in_array($file->getMimeType(), $imageTypes)
            ? 'image'
            : 'document';

        return MessageAttachment::create([
            'message_id'      => $message->id,
            'file_path'       => $filePath,
            'original_name'   => $file->getClientOriginalName(),
            'mime_type'       => $file->getMimeType() ?? 'application/octet-stream',
            'file_size'       => $file->getSize(),
            'attachment_type' => $attachmentType,
        ]);
    }
}
