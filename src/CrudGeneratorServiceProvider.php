<?php

declare(strict_types=1);

namespace SajidUlIslam\CrudGenerator;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use SajidUlIslam\CrudGenerator\Commands\ImportConfigCommand;
use SajidUlIslam\CrudGenerator\Commands\MakeCrudCommand;
use SajidUlIslam\CrudGenerator\Commands\PublishStubsCommand;
use SajidUlIslam\CrudGenerator\Commands\ValidateStubsCommand;
use SajidUlIslam\CrudGenerator\Http\Controllers\CrudGeneratorController;
use SajidUlIslam\CrudGenerator\Http\Controllers\LandingController;
use SajidUlIslam\CrudGenerator\Http\Middleware\EnsureAllowedEnvironment;
use SajidUlIslam\CrudGenerator\Services\ConfigLoader;
use SajidUlIslam\CrudGenerator\Services\FieldParser;
use SajidUlIslam\CrudGenerator\Services\FileWriter;
use SajidUlIslam\CrudGenerator\Services\RelationDetector;
use SajidUlIslam\CrudGenerator\Services\RouteRegistrar;
use SajidUlIslam\CrudGenerator\Services\StubManager;

class CrudGeneratorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config/crud-generator.php', 'crud-generator');

        $this->app->singleton(Filesystem::class, fn () => new Filesystem);

        $this->app->singleton(StubManager::class);
        $this->app->singleton(ConfigLoader::class);
        $this->app->singleton(FieldParser::class);
        $this->app->singleton(RelationDetector::class);
        $this->app->singleton(FileWriter::class);
        $this->app->singleton(RouteRegistrar::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        $this->loadViewsFrom(__DIR__.'/resources/views', 'crud-generator');

        $this->publishes([
            __DIR__.'/config/crud-generator.php' => config_path('crud-generator.php'),
        ], 'crud-generator-config');

        $this->publishes([
            __DIR__.'/resources/views' => resource_path('views/vendor/crud-generator'),
        ], 'crud-generator-views');

        $this->publishes([
            __DIR__.'/../stubs' => resource_path('stubs/vendor/crud-generator'),
        ], 'crud-generator-stubs');

        $this->publishes([
            __DIR__.'/routes/web.php' => base_path('routes/crud-generator.php'),
        ], 'crud-generator-routes');

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeCrudCommand::class,
                PublishStubsCommand::class,
                ImportConfigCommand::class,
                ValidateStubsCommand::class,
            ]);
        }

        $this->app['router']->aliasMiddleware('crud-generator.allowed', EnsureAllowedEnvironment::class);
    }
}
