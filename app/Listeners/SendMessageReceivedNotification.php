<?php

namespace App\Listeners;

use App\Events\MessageSent;
use App\Models\User;
use App\Notifications\MessageReceivedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendMessageReceivedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(MessageSent $event): void
    {
        $message      = $event->message->load(['conversation.participants', 'sender']);
        $sender       = $message->sender;
        $conversation = $message->conversation;

        $conversation->participants
            ->filter(fn (User $p) => $p->id !== $sender->id)
            ->each(fn (User $recipient) =>
                $recipient->notify(
                    new MessageReceivedNotification($message, $sender)
                )
            );
    }

    public function failed(MessageSent $event, \Throwable $e): void
    {
        Log::error('SendMessageReceivedNotification failed', [
            'message_id' => $event->message->id,
            'error'      => $e->getMessage(),
        ]);
    }
}
