<?php

namespace App\Notifications;

use App\Models\Property;
use Illuminate\Notifications\Messages\MailMessage;

class NewPropertyPendingNotification extends BaseNotification
{
    public function __construct(private readonly Property $property) {}

    public function getType(): string
    {
        return 'property_pending';
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'        => 'property_pending',
            'title'       => 'Nouvelle annonce à valider',
            'body'        => "\"{$this->property->title}\" soumise par {$this->property->owner->name} est en attente de modération.",
            'property_id' => $this->property->id,
            'action_url'  => '/admin/moderation',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nouvelle annonce en attente — ImmoConnect')
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Une nouvelle annonce est en attente de validation.")
            ->line("**{$this->property->title}** — {$this->property->city}")
            ->line("Soumise par : {$this->property->owner->name} ({$this->property->owner->email})")
            ->action('Accéder à la modération', url('/admin/moderation'));
    }
}
