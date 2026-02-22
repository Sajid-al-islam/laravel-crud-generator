<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CRUD Generator Configuration
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Allowed Environments
    |--------------------------------------------------------------------------
    |
    | The CRUD generator (both web UI and Artisan command) will only run
    | in these environments. By default, only 'local' is allowed.
    | Add more environments (e.g., 'staging', 'testing') as needed.
    |
    */
    'allowed_environments' => ['local'],
    
    'route_prefix' => 'crud-generator',
    
    'middleware' => ['web'],
    
    // Default paths for generated files
    'paths' => [
        'models' => 'App/Models',
        'controllers' => 'App/Http/Controllers',
        'requests' => 'App/Http/Requests',
        'views' => 'resources/views',
        'routes' => 'routes/web.php',
        'api_controllers' => 'App/Http/Controllers/Api',
        'api_resources' => 'App/Http/Resources',
        'api_routes' => 'routes/api.php',
    ],
    
    // Default namespace
    'namespaces' => [
        'models' => 'App\\Models',
        'controllers' => 'App\\Http\\Controllers',
        'requests' => 'App\\Http\\Requests',
        'api_controllers' => 'App\\Http\\Controllers\\Api',
        'api_resources' => 'App\\Http\\Resources',
    ],
    
    // Field types available in the generator
    'field_types' => [
        'string' => 'String',
        'text' => 'Text',
        'integer' => 'Integer',
        'boolean' => 'Boolean',
        'date' => 'Date',
        'datetime' => 'DateTime',
        'email' => 'Email',
        'password' => 'Password',
        'file' => 'File',
        'image' => 'Image',
    ],
];