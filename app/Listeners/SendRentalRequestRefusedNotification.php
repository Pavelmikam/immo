<?php

namespace App\Listeners;

use App\Events\RentalRequestRefused;
use App\Notifications\RentalRequestRefusedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendRentalRequestRefusedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(RentalRequestRefused $event): void
    {
        $event->rentalRequest->tenant->notify(
            new RentalRequestRefusedNotification($event->rentalRequest)
        );
    }

    public function failed(RentalRequestRefused $event, \Throwable $e): void
    {
        Log::error('SendRentalRequestRefusedNotification failed', [
            'rental_request_id' => $event->rentalRequest->id,
            'error'             => $e->getMessage(),
        ]);
    }
}
