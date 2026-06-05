<?php

namespace App\Listeners;

use App\Events\PropertyRejected;
use App\Notifications\PropertyRejectedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendPropertyRejectedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(PropertyRejected $event): void
    {
        $event->property->owner->notify(
            new PropertyRejectedNotification($event->property)
        );
    }

    public function failed(PropertyRejected $event, \Throwable $e): void
    {
        Log::error('SendPropertyRejectedNotification failed', [
            'property_id' => $event->property->id,
            'error'       => $e->getMessage(),
        ]);
    }
}
