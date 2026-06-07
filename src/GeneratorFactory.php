<?php

declare(strict_types=1);

namespace SajidUlIslam\CrudGenerator;

use InvalidArgumentException;
use SajidUlIslam\CrudGenerator\Contracts\GeneratorDriver;
use SajidUlIslam\CrudGenerator\Drivers\ApiDriver;
use SajidUlIslam\CrudGenerator\Drivers\BladeDriver;
use SajidUlIslam\CrudGenerator\Drivers\FilamentDriver;
use SajidUlIslam\CrudGenerator\Drivers\LivewireDriver;
use SajidUlIslam\CrudGenerator\Drivers\NovaDriver;
use SajidUlIslam\CrudGenerator\Drivers\ReactDriver;
use SajidUlIslam\CrudGenerator\Drivers\SvelteDriver;
use SajidUlIslam\CrudGenerator\Drivers\VueDriver;
use SajidUlIslam\CrudGenerator\Enums\Stack;

class GeneratorFactory
{
    /**
     * Built-in driver registry.
     *
     * @var array<string, class-string<GeneratorDriver>>
     */
    public const DRIVERS = [
        'blade' => BladeDriver::class,
        'api' => ApiDriver::class,
        'react' => ReactDriver::class,
        'vue' => VueDriver::class,
        'svelte' => SvelteDriver::class,
        'livewire' => LivewireDriver::class,
        'nova' => NovaDriver::class,
        'filament' => FilamentDriver::class,
    ];

    /**
     * Resolve a driver instance for the given stack key.
     *
     * Stack keys can come from crud-generator.json or the --stack option.
     */
    public static function make(string $stack, ?CrudDefinition $definition = null): GeneratorDriver
    {
        $stack = strtolower($stack);
        if (! in_array($stack, Stack::values(), true)) {
            throw new InvalidArgumentException(
                "Unsupported stack [{$stack}]. Supported: ".implode(', ', Stack::values())
            );
        }

        $class = self::DRIVERS[$stack] ?? null;
        if ($class === null) {
            throw new InvalidArgumentException("No driver registered for stack [{$stack}].");
        }

        /** @var GeneratorDriver $driver */
        $driver = app($class);

        return $driver;
    }

    /**
     * @return array<int, string>
     */
    public static function registeredStacks(): array
    {
        return array_keys(self::DRIVERS);
    }
}
