<?php

namespace App\Listeners;

use App\Events\PropertyApproved;
use App\Notifications\PropertyApprovedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendPropertyApprovedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(PropertyApproved $event): void
    {
        $event->property->owner->notify(
            new PropertyApprovedNotification($event->property)
        );
    }

    public function failed(PropertyApproved $event, \Throwable $e): void
    {
        Log::error('SendPropertyApprovedNotification failed', [
            'property_id' => $event->property->id,
            'error'       => $e->getMessage(),
        ]);
    }
}
