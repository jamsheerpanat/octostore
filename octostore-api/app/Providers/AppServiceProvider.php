<?php

namespace App\Providers;

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
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\OrderCreated::class,
            [\App\Listeners\SendOrderNotifications::class, 'handleOrderCreated']
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\OrderStatusUpdated::class,
            [\App\Listeners\SendOrderNotifications::class, 'handleOrderStatusUpdated']
        );
    }
}
