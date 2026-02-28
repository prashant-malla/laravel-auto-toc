<?php

namespace PrashantMalla\AutoToc;

use Illuminate\Support\ServiceProvider;

class AutoTocServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../config/auto-toc.php' => config_path('auto-toc.php'),
        ], 'auto-toc-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations'),
        ], 'auto-toc-migrations');

        // Load migrations from the package automatically
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/auto-toc.php',
            'auto-toc'
        );
    }
}
