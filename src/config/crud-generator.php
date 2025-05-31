<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CRUD Generator Configuration
    |--------------------------------------------------------------------------
    */
    
    'route_prefix' => 'crud-generator',
    
    'middleware' => ['web'],
    
    // Default paths for generated files
    'paths' => [
        'models' => 'App/Models',
        'controllers' => 'App/Http/Controllers',
        'requests' => 'App/Http/Requests',
        'views' => 'resources/views',
        'routes' => 'routes/web.php',
    ],
    
    // Default namespace
    'namespaces' => [
        'models' => 'App\\Models',
        'controllers' => 'App\\Http\\Controllers',
        'requests' => 'App\\Http\\Requests',
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