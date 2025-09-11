<?php

namespace Modules\Store\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

// Events
use Modules\Store\Events\OrderCreated;
use Modules\Store\Events\OrderStatusUpdated;
use Modules\Store\Events\PaymentStatusUpdated;
use Modules\Store\Events\RefundProcessed;
use Modules\Store\Events\ShippingUpdated;

// Listeners
use Modules\Store\Listeners\SendOrderConfirmationEmail;
use Modules\Store\Listeners\SendOrderStatusUpdateEmail;
use Modules\Store\Listeners\SendPaymentStatusNotification;
use Modules\Store\Listeners\SendRefundNotification;
use Modules\Store\Listeners\SendShippingNotification;

// Services & Repositories
use Modules\Store\Repositories\CartRepository;
use Modules\Store\Services\CartService;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        OrderCreated::class => [
            SendOrderConfirmationEmail::class,
        ],
        OrderStatusUpdated::class => [
            SendOrderStatusUpdateEmail::class,
        ],
        PaymentStatusUpdated::class => [
            SendPaymentStatusNotification::class,
        ],
        RefundProcessed::class => [
            SendRefundNotification::class,
        ],
        ShippingUpdated::class => [
            SendShippingNotification::class,
        ],
    ];

    /**
     * Indicates if events should be automatically discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = false;

    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind cart repository & service
        $this->app->singleton(CartRepository::class, fn ($app) => new CartRepository());
        $this->app->singleton(CartService::class, fn ($app) => new CartService($app->make(CartRepository::class)));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Safety net: register listeners manually
        Event::listen(OrderCreated::class, [SendOrderConfirmationEmail::class, 'handle']);
        Event::listen(OrderStatusUpdated::class, [SendOrderStatusUpdateEmail::class, 'handle']);
        Event::listen(PaymentStatusUpdated::class, [SendPaymentStatusNotification::class, 'handle']);
        Event::listen(RefundProcessed::class, [SendRefundNotification::class, 'handle']);
        Event::listen(ShippingUpdated::class, [SendShippingNotification::class, 'handle']);
    }
}
