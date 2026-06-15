<?php

namespace App\Listeners;

use App\Events\PropertySubmitted;
use App\Models\User;
use App\Notifications\NewPropertyPendingNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendPropertySubmittedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(PropertySubmitted $event): void
    {
        $property = $event->property->load('owner');

        User::where('role', 'admin')
            ->where('is_active', true)
            ->each(function (User $admin) use ($property) {
                $admin->notify(new NewPropertyPendingNotification($property));
            });
    }

    public function failed(PropertySubmitted $event, \Throwable $e): void
    {
        Log::error('SendPropertySubmittedNotification failed', [
            'property_id' => $event->property->id,
            'error'       => $e->getMessage(),
        ]);
    }
}
