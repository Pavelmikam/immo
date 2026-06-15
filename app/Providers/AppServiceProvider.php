<?php

namespace App\Providers;

use App\Contracts\DocumentServiceInterface;
use App\Contracts\ImageServiceInterface;
use App\Contracts\MessagingServiceInterface;
use App\Contracts\PropertyFilterServiceInterface;
use App\Contracts\PropertyServiceInterface;
use App\Contracts\RentalRequestServiceInterface;
use App\Models\Conversation;
use App\Models\Property;
use App\Models\RentalDocument;
use App\Models\RentalRequest;
use App\Policies\ConversationPolicy;
use App\Policies\PropertyPolicy;
use App\Policies\RentalDocumentPolicy;
use App\Policies\RentalRequestPolicy;
use App\Services\DocumentService;
use App\Services\ImageService;
use App\Services\MessagingService;
use App\Services\PropertyFilterService;
use App\Services\PropertyService;
use App\Services\RentalRequestService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\AdminLogService::class);
        $this->app->singleton(\App\Services\StatisticsService::class);
        $this->app->bind(
            \App\Contracts\NeighborhoodScoreServiceInterface::class,
            \App\Services\NeighborhoodScoreService::class
        );
        $this->app->bind(ImageServiceInterface::class, ImageService::class);
        $this->app->bind(PropertyServiceInterface::class, PropertyService::class);
        $this->app->bind(PropertyFilterServiceInterface::class, PropertyFilterService::class);
        $this->app->bind(DocumentServiceInterface::class, DocumentService::class);
        $this->app->bind(RentalRequestServiceInterface::class, RentalRequestService::class);
        $this->app->bind(MessagingServiceInterface::class, MessagingService::class);
    }

    public function boot(): void
    {
        Gate::policy(Property::class, PropertyPolicy::class);
        Gate::policy(RentalRequest::class, RentalRequestPolicy::class);
        Gate::policy(RentalDocument::class, RentalDocumentPolicy::class);
        Gate::policy(Conversation::class, ConversationPolicy::class);

        Event::listen(
            \App\Events\PropertySubmitted::class,
            \App\Listeners\SendPropertySubmittedNotification::class
        );
        Event::listen(
            \App\Events\PropertyApproved::class,
            \App\Listeners\SendPropertyApprovedNotification::class
        );
        Event::listen(
            \App\Events\PropertyRejected::class,
            \App\Listeners\SendPropertyRejectedNotification::class
        );
        Event::listen(
            \App\Events\RentalRequestCreated::class,
            \App\Listeners\SendRentalRequestReceivedNotification::class
        );
        Event::listen(
            \App\Events\RentalRequestAccepted::class,
            \App\Listeners\SendRentalRequestAcceptedNotification::class
        );
        Event::listen(
            \App\Events\RentalRequestRefused::class,
            \App\Listeners\SendRentalRequestRefusedNotification::class
        );
        Event::listen(
            \App\Events\MessageSent::class,
            \App\Listeners\SendMessageReceivedNotification::class
        );

        VerifyEmail::createUrlUsing(function ($notifiable) {
            return URL::temporarySignedRoute(
                'api.auth.verification.verify',
                now()->addMinutes(60),
                [
                    'id'   => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );
        });

        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            return rtrim(config('app.frontend_url', 'http://localhost:5173'), '/')
                . '/reset-password?token=' . $token
                . '&email=' . urlencode($notifiable->getEmailForVerification());
        });
    }
}
