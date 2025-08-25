<?php

namespace Modules\Store\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Store\Repositories\AnalyticsRepository;
use Modules\Store\Services\AnalyticsService;

class AnalyticsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(AnalyticsRepository::class, function ($app) {
            return new AnalyticsRepository();
        });

        $this->app->singleton(AnalyticsService::class, function ($app) {
            return new AnalyticsService($app->make(AnalyticsRepository::class));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
