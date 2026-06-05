<?php

namespace App\Notifications;

use App\Models\RentalRequest;
use Illuminate\Notifications\Messages\MailMessage;

class VisitScheduledNotification extends BaseNotification
{
    public function __construct(private readonly RentalRequest $rentalRequest) {}

    public function getType(): string
    {
        return 'visit_scheduled';
    }

    public function toDatabase(object $notifiable): array
    {
        $date = $this->rentalRequest->visit_scheduled_at
            ? $this->rentalRequest->visit_scheduled_at->toIso8601String()
            : null;

        $formatted = $this->rentalRequest->visit_scheduled_at
            ? $this->rentalRequest->visit_scheduled_at->format('d/m/Y à H:i')
            : '';

        return [
            'type'               => 'visit_scheduled',
            'title'              => 'Visite planifiée',
            'body'               => "Une visite est planifiée le {$formatted}.",
            'rental_request_id'  => $this->rentalRequest->id,
            'property_id'        => $this->rentalRequest->property_id,
            'visit_scheduled_at' => $date,
            'action_url'         => "/rental-requests/{$this->rentalRequest->id}",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $property  = $this->rentalRequest->property;
        $formatted = $this->rentalRequest->visit_scheduled_at
            ? $this->rentalRequest->visit_scheduled_at->format('d/m/Y à H:i')
            : '';

        return (new MailMessage)
            ->subject('Visite planifiée — ImmoConnect')
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Le propriétaire a planifié une visite le {$formatted}.")
            ->line("Adresse : {$property->address}, {$property->city}")
            ->action('Confirmer ma présence', url("/rental-requests/{$this->rentalRequest->id}"));
    }
}
