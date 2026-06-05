<?php

namespace App\Notifications;

use App\Models\RentalRequest;
use Illuminate\Notifications\Messages\MailMessage;

class RentalRequestAcceptedNotification extends BaseNotification
{
    public function __construct(private readonly RentalRequest $rentalRequest) {}

    public function getType(): string
    {
        return 'rental_request_accepted';
    }

    public function toDatabase(object $notifiable): array
    {
        $property = $this->rentalRequest->property;

        return [
            'type'             => 'rental_request_accepted',
            'title'            => 'Demande de location acceptée !',
            'body'             => "Votre demande pour \"{$property->title}\" a été acceptée.",
            'rental_request_id'=> $this->rentalRequest->id,
            'property_id'      => $this->rentalRequest->property_id,
            'action_url'       => "/rental-requests/{$this->rentalRequest->id}",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $property = $this->rentalRequest->property;

        return (new MailMessage)
            ->subject('Votre demande a été acceptée — ImmoConnect')
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Bonne nouvelle ! Votre demande pour \"{$property->title}\" a été acceptée.")
            ->action('Voir les détails', url("/rental-requests/{$this->rentalRequest->id}"))
            ->line('Contactez le propriétaire pour organiser votre emménagement.');
    }
}
