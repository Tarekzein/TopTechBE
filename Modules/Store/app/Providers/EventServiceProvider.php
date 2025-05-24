<?php

namespace Modules\Store\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Store\Repositories\CartRepository;
use Modules\Store\Services\CartService;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void
    {
        //
    }

    public function register()
    {
        $this->app->singleton(CartRepository::class, function ($app) {
            return new CartRepository();
        });
        $this->app->singleton(CartService::class, function ($app) {
            return new CartService($app->make(CartRepository::class));
        });
    }
}
