<?php

namespace App\Providers;

use App\Contracts\ImageServiceInterface;
use App\Contracts\PropertyFilterServiceInterface;
use App\Contracts\PropertyServiceInterface;
use App\Models\Property;
use App\Policies\PropertyPolicy;
use App\Services\ImageService;
use App\Services\PropertyFilterService;
use App\Services\PropertyService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ImageServiceInterface::class, ImageService::class);
        $this->app->bind(PropertyServiceInterface::class, PropertyService::class);
        $this->app->bind(PropertyFilterServiceInterface::class, PropertyFilterService::class);
    }

    public function boot(): void
    {
        Gate::policy(Property::class, PropertyPolicy::class);

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
