<?php

namespace Modules\Store\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Store\Repositories\CartRepository;
use Modules\Store\Services\CartService;
use Modules\Store\Events\OrderCreated;
use Modules\Store\Events\OrderStatusUpdated;
use Modules\Store\Listeners\SendOrderConfirmationEmail;
use Modules\Store\Listeners\SendOrderStatusUpdateEmail;
class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        OrderCreated::class => [
            SendOrderConfirmationEmail::class,
        ],
        OrderStatusUpdated::class => [
            SendOrderStatusUpdateEmail::class,
        ]];

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
