<?php

namespace App\Notifications;

use App\Models\Property;
use Illuminate\Notifications\Messages\MailMessage;

class PropertyRejectedNotification extends BaseNotification
{
    public function __construct(private readonly Property $property) {}

    public function getType(): string
    {
        return 'property_rejected';
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'             => 'property_rejected',
            'title'            => 'Annonce non approuvée',
            'body'             => "Votre annonce \"{$this->property->title}\" n'a pas été approuvée.",
            'property_id'      => $this->property->id,
            'rejection_reason' => $this->property->rejection_reason,
            'action_url'       => "/my-properties/{$this->property->id}/edit",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Action requise sur votre annonce — ImmoConnect')
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre annonce \"{$this->property->title}\" n'a pas été approuvée.")
            ->line("Motif : {$this->property->rejection_reason}")
            ->action('Modifier mon annonce', url("/my-properties/{$this->property->id}/edit"));
    }
}
