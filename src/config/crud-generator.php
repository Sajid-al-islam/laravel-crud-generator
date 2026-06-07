<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default stack
    |--------------------------------------------------------------------------
    |
    | The stack to use when none is specified. Override per-project with the
    | CRUD_STACK env variable.
    |
    */
    'default_stack' => env('CRUD_STACK', 'blade'),

    'default_layout' => env('CRUD_LAYOUT', 'layouts.app'),

    /*
    |--------------------------------------------------------------------------
    | Allowed environments
    |--------------------------------------------------------------------------
    |
    | The CRUD generator (web UI and CLI) is only available in these
    | environments. Add more as needed.
    |
    */
    'allowed_environments' => ['local'],

    /*
    |--------------------------------------------------------------------------
    | Web UI routing
    |--------------------------------------------------------------------------
    */
    'route_prefix' => 'crud-generator',
    'route_middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Stack driver registry
    |--------------------------------------------------------------------------
    |
    | Map a stack key to a driver class. Each driver handles all files for
    | a given stack (controller, model, frontend pages, etc).
    |
    */
    'stacks' => [
        'blade' => \SajidUlIslam\CrudGenerator\Drivers\BladeDriver::class,
        'api' => \SajidUlIslam\CrudGenerator\Drivers\ApiDriver::class,
        'react' => \SajidUlIslam\CrudGenerator\Drivers\ReactDriver::class,
        'vue' => \SajidUlIslam\CrudGenerator\Drivers\VueDriver::class,
        'svelte' => \SajidUlIslam\CrudGenerator\Drivers\SvelteDriver::class,
        'livewire' => \SajidUlIslam\CrudGenerator\Drivers\LivewireDriver::class,
        'nova' => \SajidUlIslam\CrudGenerator\Drivers\NovaDriver::class,
        'filament' => \SajidUlIslam\CrudGenerator\Drivers\FilamentDriver::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Service layer
    |--------------------------------------------------------------------------
    |
    | 'suggested' (default) — CLI wizard asks the user.
    | 'required'            — service is always generated.
    | 'off'                 — service is never generated.
    |
    */
    'service_layer' => [
        'pattern' => env('CRUD_SERVICE_PATTERN', 'suggested'),
        'namespace' => env('CRUD_SERVICE_NS', 'App\\Services'),
        'path' => app_path('Services'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Repository layer
    |--------------------------------------------------------------------------
    */
    'repository' => [
        'enabled' => env('CRUD_REPOSITORY', false),
        'namespace' => env('CRUD_REPO_NS', 'App\\Repositories'),
        'contracts_namespace' => env('CRUD_REPO_CONTRACT_NS', 'App\\Repositories\\Contracts'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom stub paths
    |--------------------------------------------------------------------------
    */
    'custom_stubs_path' => base_path('crud-stubs'),
    'custom_config_path' => base_path('crud-generator.json'),

    /*
    |--------------------------------------------------------------------------
    | Frontend defaults
    |--------------------------------------------------------------------------
    */
    'frontend' => [
        'page_prefix' => env('CRUD_PAGE_PREFIX', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Field types
    |--------------------------------------------------------------------------
    */
    'field_types' => [
        'string' => 'String',
        'text' => 'Text',
        'longText' => 'Long Text',
        'integer' => 'Integer',
        'bigInteger' => 'Big Integer',
        'boolean' => 'Boolean',
        'date' => 'Date',
        'datetime' => 'DateTime',
        'decimal' => 'Decimal',
        'float' => 'Float',
        'json' => 'JSON',
        'email' => 'Email',
        'password' => 'Password',
        'file' => 'File',
        'image' => 'Image',
    ],
];
