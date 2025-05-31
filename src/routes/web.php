<?php

use Illuminate\Support\Facades\Route;
use SajidUlIslam\CrudGenerator\Http\Controllers\CrudGeneratorController;

Route::group([
    'prefix' => config('crud-generator.route_prefix', 'crud-generator'),
    'middleware' => config('crud-generator.middleware', ['web']),
], function () {
    
    Route::get('/', [CrudGeneratorController::class, 'index'])->name('crud-generator.index');
    Route::post('/generate', [CrudGeneratorController::class, 'generate'])->name('crud-generator.generate');
    Route::get('/models', [CrudGeneratorController::class, 'getModels'])->name('crud-generator.models');
    
});