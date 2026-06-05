<?php

namespace App\Notifications;

use App\Models\Property;
use Illuminate\Notifications\Messages\MailMessage;

class PropertyApprovedNotification extends BaseNotification
{
    public function __construct(private readonly Property $property) {}

    public function getType(): string
    {
        return 'property_approved';
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'        => 'property_approved',
            'title'       => 'Annonce approuvée !',
            'body'        => "Votre annonce \"{$this->property->title}\" est maintenant visible.",
            'property_id' => $this->property->id,
            'action_url'  => "/properties/{$this->property->id}",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre annonce est en ligne — ImmoConnect')
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre annonce \"{$this->property->title}\" a été approuvée et est visible.")
            ->action('Voir mon annonce', url("/properties/{$this->property->id}"));
    }
}
