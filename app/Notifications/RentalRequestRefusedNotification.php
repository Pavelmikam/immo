<?php

namespace App\Notifications;

use App\Models\RentalRequest;
use Illuminate\Notifications\Messages\MailMessage;

class RentalRequestRefusedNotification extends BaseNotification
{
    public function __construct(private readonly RentalRequest $rentalRequest) {}

    public function getType(): string
    {
        return 'rental_request_refused';
    }

    public function toDatabase(object $notifiable): array
    {
        $property = $this->rentalRequest->property;

        return [
            'type'             => 'rental_request_refused',
            'title'            => 'Demande non retenue',
            'body'             => "Votre demande pour \"{$property->title}\" n'a pas été retenue.",
            'rental_request_id'=> $this->rentalRequest->id,
            'property_id'      => $this->rentalRequest->property_id,
            'owner_response'   => $this->rentalRequest->owner_response,
            'action_url'       => '/properties',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $property = $this->rentalRequest->property;
        $mail     = (new MailMessage)
            ->subject('Réponse concernant votre demande — ImmoConnect')
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre demande pour \"{$property->title}\" n'a pas été retenue.");

        if ($this->rentalRequest->owner_response) {
            $mail->line("Motif communiqué : {$this->rentalRequest->owner_response}");
        }

        return $mail->action("Rechercher d'autres biens", url('/properties'));
    }
}
