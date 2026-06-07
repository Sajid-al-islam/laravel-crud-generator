<?php

declare(strict_types=1);

namespace SajidUlIslam\CrudGenerator\Contracts;

use SajidUlIslam\CrudGenerator\CrudDefinition;

interface GeneratorDriver
{
    /**
     * Run the generator for the given definition.
     *
     * Implementations should return an array of file paths indexed by role
     * (e.g. ['controller' => '/abs/...', 'views' => [...]]).
     *
     * @return array<string, mixed>
     */
    public function generate(CrudDefinition $definition): array;

    /**
     * Required PHP/composer dependencies for this driver to work.
     * The package may fail with a clear message if missing.
     *
     * @return array<int, string>
     */
    public function getRequiredDependencies(): array;

    /**
     * The package-internal stub path for this driver.
     * E.g. __DIR__.'/../../stubs/react'.
     */
    public function getStubPath(): string;

    /**
     * Human-readable name for the stack.
     */
    public function getStackName(): string;
}
