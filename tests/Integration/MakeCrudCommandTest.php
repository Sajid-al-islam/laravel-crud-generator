<?php

declare(strict_types=1);

namespace SajidUlIslam\CrudGenerator\Tests\Integration;

use Illuminate\Filesystem\Filesystem;
use SajidUlIslam\CrudGenerator\Commands\MakeCrudCommand;
use SajidUlIslam\CrudGenerator\GeneratorFactory;
use SajidUlIslam\CrudGenerator\Tests\TestCase;

class MakeCrudCommandTest extends TestCase
{
    public function test_command_is_registered(): void
    {
        $this->assertArrayHasKey(MakeCrudCommand::class, $this->app->all());
    }

    public function test_all_stacks_have_drivers(): void
    {
        $stacks = ['blade', 'api', 'react', 'vue', 'svelte', 'livewire', 'nova', 'filament'];
        foreach ($stacks as $stack) {
            $driver = GeneratorFactory::make($stack);
            $this->assertNotNull($driver->getStackName());
            $this->assertNotEmpty($driver->getStubPath());
        }
    }
}
