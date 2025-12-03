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
        
        // Load config
        $this->mergeConfigFrom(__DIR__ . '/config/crud-generator.php', 'crud-generator');
        
        // Publishing is only necessary when using the package in a Laravel application
        if ($this->app->runningInConsole()) {
            // Publish configuration file
            $this->publishes([
                __DIR__ . '/config/crud-generator.php' => config_path('crud-generator.php'),
            ], ['crud-generator-config', 'crud-generator']);
            
            // Publish views
            $this->publishes([
                __DIR__ . '/resources/views' => resource_path('views/vendor/crud-generator'),
            ], ['crud-generator-views', 'crud-generator']);
            
            // Publish stubs (most important for customization)
            $this->publishes([
                __DIR__ . '/stubs' => resource_path('stubs/vendor/crud-generator'),
            ], ['crud-generator-stubs', 'crud-generator']);
            
            // Publish routes for customization
            $this->publishes([
                __DIR__ . '/routes/web.php' => base_path('routes/crud-generator.php'),
            ], ['crud-generator-routes', 'crud-generator']);
            
            // Register commands
            $this->commands([
                Commands\CrudGenerateCommand::class,
            ]);
        }
    }
}