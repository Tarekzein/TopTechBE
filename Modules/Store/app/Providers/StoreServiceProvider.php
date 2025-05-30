<?php

namespace Modules\Store\Providers;


use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Store\App\Console\Commands\UpdateExchangeRates;
use Modules\Store\App\Repositories\CurrencyRepository;
use Modules\Store\App\Repositories\SettingRepository;
use Modules\Store\App\Services\CurrencyService;
use Modules\Store\App\Services\SettingService;
use Modules\Store\Interfaces\CurrencyRepositoryInterface;
use Modules\Store\Interfaces\CurrencyServiceInterface;
use Modules\Store\Interfaces\SettingRepositoryInterface;
use Modules\Store\Interfaces\SettingServiceInterface;
use Modules\Store\Models\BillingAddress;
use Modules\Store\Models\ShippingAddress;
use Modules\Store\Policies\BillingAddressPolicy;
use Modules\Store\Policies\ShippingAddressPolicy;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class StoreServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Store';

    protected string $nameLower = 'store';

    /**
     * The commands to register.
     *
     * @var array
     */
    protected $commands = [
        UpdateExchangeRates::class,
    ];

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $this->registerPolicies();
        $this->loadRoutesFrom(module_path($this->name, 'routes/api.php'));
        $this->publishes([
            module_path($this->name, 'config/store.php') => config_path('store.php'),
        ], 'store-config');
        $this->publishes([
            module_path($this->name, 'database/migrations') => database_path('migrations'),
        ], 'store-migrations');
        $this->publishes([
            module_path($this->name, 'resources/lang') => resource_path('lang/vendor/store'),
        ], 'store-translations');
        $this->publishes([
            module_path($this->name, 'resources/views') => resource_path('views/vendor/store'),
        ], 'store-views');
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
        $this->registerCommands();
        $this->bindRepositories();
        $this->bindServices();
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands($this->commands);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // $this->app->booted(function () {
        //     $schedule = $this->app->make(Schedule::class);
        //     $schedule->command('inspire')->hourly();
        // });
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $relativeConfigPath = config('modules.paths.generator.config.path');
        $configPath = module_path($this->name, $relativeConfigPath);

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $relativePath = str_replace($configPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $configKey = $this->nameLower . '.' . str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $relativePath);
                    $key = ($relativePath === 'config.php') ? $this->nameLower : $configKey;

                    $this->publishes([$file->getPathname() => config_path($relativePath)], 'config');
                    $this->mergeConfigFrom($file->getPathname(), $key);
                }
            }
        }
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        $componentNamespace = $this->module_namespace($this->name, $this->app_path(config('modules.paths.generator.component-class.path')));
        Blade::componentNamespace($componentNamespace, $this->nameLower);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }

    protected function registerPolicies()
    {
        Gate::policy(BillingAddress::class, BillingAddressPolicy::class);
        Gate::policy(ShippingAddress::class, ShippingAddressPolicy::class);
    }

    protected function bindRepositories()
    {
        $this->app->bind(CurrencyRepositoryInterface::class, CurrencyRepository::class);
        $this->app->bind(SettingRepositoryInterface::class, SettingRepository::class);
        $this->app->bind(OrderRepository::class, function ($app) {
            return new OrderRepository();
        });
    }

    protected function bindServices()
    {
        $this->app->bind(CurrencyServiceInterface::class, CurrencyService::class);
        $this->app->bind(SettingServiceInterface::class, SettingService::class);
    }
}
