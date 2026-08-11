<?php

namespace App\Providers;

use Aws\Sns\MessageValidator;
use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Verifies SNS signatures on the SES bounce/complaint webhook.
        // Bound here so tests can swap in a validator with a stubbed
        // certificate client instead of reaching out to AWS.
        $this->app->singleton(MessageValidator::class, fn () => new MessageValidator);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // 30 days (in minutes) for "remember me" cookie duration.
        /** @var SessionGuard $guard */
        $guard = Auth::guard('web');
        $guard->setRememberDuration(60 * 24 * 30);
    }
}
