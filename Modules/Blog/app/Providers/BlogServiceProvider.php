<?php

namespace Modules\Blog\App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Modules\Blog\App\Repositories\Interfaces\PostRepositoryInterface;
use Modules\Blog\App\Repositories\Interfaces\BlogCategoryRepositoryInterface;
use Modules\Blog\App\Repositories\Interfaces\TagRepositoryInterface;
use Modules\Blog\App\Repositories\Interfaces\CommentRepositoryInterface;
use Modules\Blog\App\Repositories\PostRepository;
use Modules\Blog\App\Repositories\BlogCategoryRepository;
use Modules\Blog\App\Repositories\TagRepository;
use Modules\Blog\App\Repositories\CommentRepository;
use Modules\Blog\App\Services\Interfaces\PostServiceInterface;
use Modules\Blog\App\Services\Interfaces\BlogCategoryServiceInterface;
use Modules\Blog\App\Services\Interfaces\TagServiceInterface;
use Modules\Blog\App\Services\Interfaces\CommentServiceInterface;
use Modules\Blog\App\Services\PostService;
use Modules\Blog\App\Services\BlogCategoryService;
use Modules\Blog\App\Services\TagService;
use Modules\Blog\App\Services\CommentService;

class BlogServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $moduleName = 'Blog';
    protected string $moduleNameLower = 'blog';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->registerRoutes();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        // Register Repositories
        $this->app->bind(PostRepositoryInterface::class, PostRepository::class);
        $this->app->bind(BlogCategoryRepositoryInterface::class, BlogCategoryRepository::class);
        $this->app->bind(TagRepositoryInterface::class, TagRepository::class);
        $this->app->bind(CommentRepositoryInterface::class, CommentRepository::class);

        // Register Services
        $this->app->bind(PostServiceInterface::class, PostService::class);
        $this->app->bind(BlogCategoryServiceInterface::class, BlogCategoryService::class);
        $this->app->bind(TagServiceInterface::class, TagService::class);
        $this->app->bind(CommentServiceInterface::class, CommentService::class);
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        // $this->commands([]);
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
        $langPath = resource_path('lang/modules/'.$this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'lang'), $this->moduleNameLower);
            $this->loadJsonTranslationsFrom(module_path($this->moduleName, 'lang'));
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $relativeConfigPath = config('modules.paths.generator.config.path');
        $configPath = module_path($this->moduleName, $relativeConfigPath);

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $relativePath = str_replace($configPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $configKey = $this->moduleNameLower . '.' . str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $relativePath);
                    $key = ($relativePath === 'config.php') ? $this->moduleNameLower : $configKey;

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
        $viewPath = resource_path('views/modules/'.$this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->moduleNameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);

        $componentNamespace = $this->module_namespace($this->moduleName, $this->app_path(config('modules.paths.generator.component-class.path')));
        Blade::componentNamespace($componentNamespace, $this->moduleNameLower);
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
            if (is_dir($path.'/modules/'.$this->moduleNameLower)) {
                $paths[] = $path.'/modules/'.$this->moduleNameLower;
            }
        }

        return $paths;
    }

    protected function registerRoutes(): void
    {
        $this->loadRoutesFrom(module_path($this->moduleName, 'routes/api.php'));
    }
}
