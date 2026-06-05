<?php

namespace App\Notifications;

use App\Models\RentalRequest;
use Illuminate\Notifications\Messages\MailMessage;

class RentalRequestReceivedNotification extends BaseNotification
{
    public function __construct(private readonly RentalRequest $rentalRequest) {}

    public function getType(): string
    {
        return 'rental_request_received';
    }

    public function toDatabase(object $notifiable): array
    {
        $tenant   = $this->rentalRequest->tenant;
        $property = $this->rentalRequest->property;

        return [
            'type'             => 'rental_request_received',
            'title'            => 'Nouvelle demande de location',
            'body'             => "{$tenant->name} souhaite louer votre bien \"{$property->title}\".",
            'rental_request_id'=> $this->rentalRequest->id,
            'property_id'      => $this->rentalRequest->property_id,
            'tenant_id'        => $this->rentalRequest->tenant_id,
            'action_url'       => "/rental-requests/{$this->rentalRequest->id}",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tenant   = $this->rentalRequest->tenant;
        $property = $this->rentalRequest->property;

        return (new MailMessage)
            ->subject('Nouvelle demande de location — ImmoConnect')
            ->greeting("Bonjour {$notifiable->name},")
            ->line("{$tenant->name} souhaite louer votre bien \"{$property->title}\".")
            ->action('Voir la demande', url("/rental-requests/{$this->rentalRequest->id}"))
            ->line('Connectez-vous pour accepter ou refuser cette demande.');
    }
}
