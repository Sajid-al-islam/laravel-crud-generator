<?php

declare(strict_types=1);

namespace SajidUlIslam\CrudGenerator\Drivers;

use Illuminate\Filesystem\Filesystem;
use SajidUlIslam\CrudGenerator\Contracts\GeneratorDriver;
use SajidUlIslam\CrudGenerator\CrudDefinition;
use SajidUlIslam\CrudGenerator\Enums\Stack;
use SajidUlIslam\CrudGenerator\Services\ConfigLoader;
use SajidUlIslam\CrudGenerator\Services\FieldParser;
use SajidUlIslam\CrudGenerator\Services\FileWriter;
use SajidUlIslam\CrudGenerator\Services\RouteRegistrar;
use SajidUlIslam\CrudGenerator\Services\StubManager;

abstract class AbstractDriver implements GeneratorDriver
{
    public function __construct(
        protected StubManager $stubs,
        protected Filesystem $files,
        protected FileWriter $writer,
        protected FieldParser $fields,
        protected ConfigLoader $config,
        protected RouteRegistrar $routes,
    ) {}

    abstract public function getStackName(): string;

    public function getStubPath(): string
    {
        return $this->stubs->packageStubPath($this->getStackKey()->value);
    }

    public function getStackKey(): Stack
    {
        return $this->resolveStackKey();
    }

    abstract protected function resolveStackKey(): Stack;

    public function getRequiredDependencies(): array
    {
        return [];
    }

    public function assertDependencies(): void
    {
        foreach ($this->getRequiredDependencies() as $class) {
            if (! class_exists($class)) {
                throw new \RuntimeException(
                    "Stack [{$this->getStackName()}] requires the package containing [{$class}]. ".
                    'Please install it via composer before using this driver.'
                );
            }
        }
    }

    /**
     * Render a stub and write it to its target path.
     *
     * @param  array<string, string|int|bool|null>  $extra
     * @return array{path:string, status:string}
     */
    protected function makeFile(
        CrudDefinition $definition,
        string $stubName,
        string $destination,
        array $extra = [],
        bool $force = false,
    ): array {
        $vars = array_merge($definition->variables(), $extra);
        $content = $this->stubs->resolveAndRender($stubName, $this->getStackKey()->value, $vars);

        return $this->writer->write($destination, $content, $force);
    }

    /**
     * Generate a shared stub (model, migration, requests, service, repository)
     * into the right path with the right content.
     *
     * @param  array<string, string|int|bool|null>  $extra
     * @return array{path:string, status:string}
     */
    protected function makeShared(
        CrudDefinition $definition,
        string $stubName,
        string $destination,
        array $extra = [],
        bool $force = false,
    ): array {
        $vars = array_merge($definition->variables(), $extra);
        $content = $this->stubs->resolveAndRender($stubName, 'shared', $vars);

        return $this->writer->write($destination, $content, $force);
    }
}
