<?php

declare(strict_types=1);

namespace SajidUlIslam\CrudGenerator\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use SajidUlIslam\CrudGenerator\CrudGeneratorServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            CrudGeneratorServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('crud-generator.allowed_environments', ['testing', 'local']);
    }
}
