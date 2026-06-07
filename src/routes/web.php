<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use SajidUlIslam\CrudGenerator\Http\Controllers\CrudGeneratorController;
use SajidUlIslam\CrudGenerator\Http\Controllers\LandingController;
use SajidUlIslam\CrudGenerator\Http\Middleware\EnsureAllowedEnvironment;

Route::get('/', [LandingController::class, 'index'])->name('crud-generator.landing');

Route::group([
    'prefix' => config('crud-generator.route_prefix', 'crud-generator'),
    'middleware' => array_merge(
        config('crud-generator.route_middleware', ['web']),
        [EnsureAllowedEnvironment::class]
    ),
    'as' => 'crud-generator.',
], function () {
    Route::get('/', [CrudGeneratorController::class, 'index'])->name('index');
    Route::post('/generate', [CrudGeneratorController::class, 'generate'])->name('generate');
    Route::post('/preview', [CrudGeneratorController::class, 'preview'])->name('preview');
    Route::get('/models', [CrudGeneratorController::class, 'getModels'])->name('models');
});
