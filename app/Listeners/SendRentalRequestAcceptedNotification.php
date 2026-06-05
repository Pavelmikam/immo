<?php

namespace App\Listeners;

use App\Events\RentalRequestAccepted;
use App\Notifications\RentalRequestAcceptedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendRentalRequestAcceptedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(RentalRequestAccepted $event): void
    {
        $event->rentalRequest->tenant->notify(
            new RentalRequestAcceptedNotification($event->rentalRequest)
        );
    }

    public function failed(RentalRequestAccepted $event, \Throwable $e): void
    {
        Log::error('SendRentalRequestAcceptedNotification failed', [
            'rental_request_id' => $event->rentalRequest->id,
            'error'             => $e->getMessage(),
        ]);
    }
}
