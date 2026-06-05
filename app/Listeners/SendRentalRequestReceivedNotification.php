<?php

namespace App\Listeners;

use App\Events\RentalRequestCreated;
use App\Notifications\RentalRequestReceivedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendRentalRequestReceivedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(RentalRequestCreated $event): void
    {
        $event->rentalRequest->property->owner->notify(
            new RentalRequestReceivedNotification($event->rentalRequest)
        );
    }

    public function failed(RentalRequestCreated $event, \Throwable $e): void
    {
        Log::error('SendRentalRequestReceivedNotification failed', [
            'rental_request_id' => $event->rentalRequest->id,
            'error'             => $e->getMessage(),
        ]);
    }
}
