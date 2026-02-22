<?php

use Illuminate\Support\Facades\Route;
use SajidUlIslam\CrudGenerator\Http\Controllers\CrudGeneratorController;
use SajidUlIslam\CrudGenerator\Http\Middleware\EnsureAllowedEnvironment;

Route::group([
    'prefix' => config('crud-generator.route_prefix', 'crud-generator'),
    'middleware' => array_merge(
        config('crud-generator.middleware', ['web']),
        [EnsureAllowedEnvironment::class]
    ),
], function () {
    
    Route::get('/', [CrudGeneratorController::class, 'index'])->name('crud-generator.index');
    Route::post('/generate', [CrudGeneratorController::class, 'generate'])->name('crud-generator.generate');
    Route::get('/models', [CrudGeneratorController::class, 'getModels'])->name('crud-generator.models');
    
});