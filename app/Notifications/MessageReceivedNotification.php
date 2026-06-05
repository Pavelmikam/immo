<?php

namespace App\Notifications;

use App\Models\Message;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

class MessageReceivedNotification extends BaseNotification
{
    public function __construct(
        private readonly Message $message,
        private readonly User $sender
    ) {}

    public function getType(): string
    {
        return 'message_received';
    }

    public function toDatabase(object $notifiable): array
    {
        $body    = $this->message->body;
        $preview = mb_strlen($body) > 50
            ? mb_substr($body, 0, 50) . '...'
            : $body;

        return [
            'type'            => 'message_received',
            'title'           => "Nouveau message de {$this->sender->name}",
            'body'            => $preview,
            'conversation_id' => $this->message->conversation_id,
            'message_id'      => $this->message->id,
            'sender_id'       => $this->sender->id,
            'property_id'     => $this->message->conversation->property_id,
            'action_url'      => "/conversations/{$this->message->conversation_id}",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $body    = $this->message->body;
        $preview = mb_strlen($body) > 50
            ? mb_substr($body, 0, 50) . '...'
            : $body;

        return (new MailMessage)
            ->subject('Nouveau message sur ImmoConnect')
            ->greeting("Bonjour {$notifiable->name},")
            ->line("{$this->sender->name} vous a envoyé un message.")
            ->line($preview)
            ->action('Répondre', url("/conversations/{$this->message->conversation_id}"))
            ->line('Connectez-vous pour voir le message complet.');
    }
}
