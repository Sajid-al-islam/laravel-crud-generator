<?php

namespace SajidUlIslam\CrudGenerator;

use Illuminate\Support\ServiceProvider;

class CrudGeneratorServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register package services
    }

    public function boot()
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        
        // Load views
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'crud-generator');
        
        // Publish views (optional)
        $this->publishes([
            __DIR__ . '/resources/views' => resource_path('views/vendor/crud-generator'),
        ], 'crud-generator-views');
        
        // Publish config (optional)
        $this->publishes([
            __DIR__ . '/config/crud-generator.php' => config_path('crud-generator.php'),
        ], 'crud-generator-config');
        
        // Load config
        $this->mergeConfigFrom(__DIR__ . '/config/crud-generator.php', 'crud-generator');
        
        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\CrudGenerateCommand::class,
            ]);
        }
    }
}