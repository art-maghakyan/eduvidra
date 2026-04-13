<?php

namespace App\Providers;

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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // 30 days (in minutes) for "remember me" cookie duration.
        /** @var \Illuminate\Auth\SessionGuard $guard */
        $guard = Auth::guard('web');
        $guard->setRememberDuration(60 * 24 * 30);
    }
}
