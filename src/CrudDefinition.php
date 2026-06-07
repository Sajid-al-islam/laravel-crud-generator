<?php

declare(strict_types=1);

namespace SajidUlIslam\CrudGenerator;

use InvalidArgumentException;
use SajidUlIslam\CrudGenerator\Enums\Stack;

final class CrudDefinition
{
    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        public readonly string $modelName,
        public readonly string $tableName,
        public readonly array $fields,
        public readonly string $stack,
        public readonly bool $withService = false,
        public readonly bool $withRepository = false,
        public readonly ?string $customStubPath = null,
        public readonly array $options = [],
    ) {
        if ($this->modelName === '') {
            throw new InvalidArgumentException('Model name cannot be empty.');
        }
        if ($this->tableName === '') {
            throw new InvalidArgumentException('Table name cannot be empty.');
        }
        if (! in_array($this->stack, Stack::values(), true)) {
            throw new InvalidArgumentException(
                "Unsupported stack [{$this->stack}]. Supported: ".implode(', ', Stack::values())
            );
        }
        if ($this->withRepository && ! $this->withService) {
            throw new InvalidArgumentException(
                'Repository layer requires the service layer to be enabled.'
            );
        }
    }

    public function stackEnum(): Stack
    {
        return Stack::from($this->stack);
    }

    public function modelNamePlural(): string
    {
        return str($this->modelName)->plural()->toString();
    }

    public function modelNameLower(): string
    {
        return str($this->modelName)->lower()->toString();
    }

    public function modelNameLowerPlural(): string
    {
        return str($this->modelName)->plural()->lower()->toString();
    }

    public function modelNameSnake(): string
    {
        return str($this->modelName)->snake()->toString();
    }

    public function modelNameKebab(): string
    {
        return str($this->modelName)->kebab()->toString();
    }

    public function modelNameCamel(): string
    {
        return str($this->modelName)->camel()->toString();
    }

    public function modelVariable(): string
    {
        return str($this->modelName)->camel()->toString();
    }

    public function modelPluralVariable(): string
    {
        return str($this->modelName)->plural()->camel()->toString();
    }

    public function routePrefix(): string
    {
        return str($this->modelName)->plural()->lower()->toString();
    }

    /**
     * Build the variable map used by all stub renderers.
     *
     * @return array<string, string>
     */
    public function variables(): array
    {
        $componentLibrary = 'shadcn/ui';
        if (function_exists('app') && app()->bound(\SajidUlIslam\CrudGenerator\Services\ConfigLoader::class)) {
            $cfg = app(\SajidUlIslam\CrudGenerator\Services\ConfigLoader::class)->all();
            $componentLibrary = (string) ($cfg['componentLibrary'] ?? 'shadcn/ui');
        }

        $serviceNs = function_exists('config')
            ? config('crud-generator.service_layer.namespace', 'App\\Services')
            : 'App\\Services';
        $repoNs = function_exists('config')
            ? config('crud-generator.repository.namespace', 'App\\Repositories')
            : 'App\\Repositories';
        $repoContractNs = function_exists('config')
            ? config('crud-generator.repository.contracts_namespace', 'App\\Repositories\\Contracts')
            : 'App\\Repositories\\Contracts';

        return [
            'modelName' => $this->modelName,
            'modelNamePlural' => $this->modelNamePlural(),
            'modelNameLower' => $this->modelNameLower(),
            'modelNameLowerPlural' => $this->modelNameLowerPlural(),
            'modelNameSnake' => $this->modelNameSnake(),
            'modelNameKebab' => $this->modelNameKebab(),
            'modelNameCamel' => $this->modelNameCamel(),
            'modelVariable' => $this->modelVariable(),
            'modelPluralVariable' => $this->modelPluralVariable(),
            'routePrefix' => $this->routePrefix(),
            'tableName' => $this->tableName,
            'namespace' => 'App\\Http\\Controllers',
            'modelNamespace' => 'App\\Models',
            'serviceNamespace' => $serviceNs,
            'repositoryNamespace' => $repoNs,
            'repositoryContractNamespace' => $repoContractNs,
            'stack' => $this->stack,
            'withService' => $this->withService ? 'true' : 'false',
            'withRepository' => $this->withRepository ? 'true' : 'false',
            'componentLibrary' => $componentLibrary,
            'fields' => json_encode($this->fields, JSON_PRETTY_PRINT),
        ];
    }
}
