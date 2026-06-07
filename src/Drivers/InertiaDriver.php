<?php

declare(strict_types=1);

namespace SajidUlIslam\CrudGenerator\Drivers;

use Illuminate\Support\Str;
use SajidUlIslam\CrudGenerator\CrudDefinition;
use SajidUlIslam\CrudGenerator\Enums\Stack;

abstract class InertiaDriver extends AbstractDriver
{
    public function getRequiredDependencies(): array
    {
        return [
            \Inertia\Inertia::class,
        ];
    }

    public function generate(CrudDefinition $definition): array
    {
        $this->assertDependencies();

        $force = (bool) ($definition->options['force'] ?? false);
        $writes = [];

        // Backend
        $writes['controller'] = $this->makeFile(
            $definition,
            'controller.stub',
            app_path('Http/Controllers/'.$definition->modelName.'Controller.php'),
            $this->controllerVariables($definition),
            $force,
        );

        $writes['model'] = $this->makeShared(
            $definition,
            'model.stub',
            app_path('Models/'.$definition->modelName.'.php'),
            $this->sharedVariables($definition),
            $force,
        );

        if ($definition->options['with_migration'] ?? true) {
            $writes['migration'] = $this->makeMigration($definition, $force);
        }

        $writes['store_request'] = $this->makeFile(
            $definition,
            'store-request.stub',
            app_path('Http/Requests/Store'.$definition->modelName.'Request.php'),
            array_merge(['namespace' => 'App\\Http'], $definition->variables()),
            $force,
        );

        $writes['update_request'] = $this->makeFile(
            $definition,
            'update-request.stub',
            app_path('Http/Requests/Update'.$definition->modelName.'Request.php'),
            array_merge(['namespace' => 'App\\Http'], $definition->variables()),
            $force,
        );

        if ($definition->withService) {
            $writes['service'] = $this->generateService($definition, $force);
        }

        if ($definition->withRepository) {
            $writes['repository_interface'] = $this->generateRepositoryInterface($definition, $force);
            $writes['repository'] = $this->generateRepository($definition, $force);
            $this->bindRepository($definition);
        }

        if ($definition->options['register_routes'] ?? true) {
            $routesPath = $this->routes->register($definition);
            $writes['routes'] = [
                'path' => $routesPath ?? '',
                'status' => $routesPath ? 'modified' : 'skipped',
            ];
        }

        // Frontend pages + types
        $frontend = $this->generateFrontend($definition, $force);
        $writes = array_merge($writes, $frontend);

        return $writes;
    }

    /**
     * Build the controller-level variable map.
     *
     * @return array<string, string>
     */
    protected function controllerVariables(CrudDefinition $definition): array
    {
        $vars = $definition->variables();
        $vars['useStatements'] = $this->buildControllerUseStatements($definition);
        $vars['controllerProperties'] = $this->buildControllerProperties($definition);
        $vars['serviceStore'] = $this->buildServiceCall('store', $definition);
        $vars['serviceUpdate'] = $this->buildServiceCall('update', $definition);
        $vars['serviceDestroy'] = $this->buildServiceCall('destroy', $definition);
        $vars['pagePrefix'] = (string) (config('crud-generator.frontend.page_prefix', '') ?? '');

        return $vars;
    }

    /**
     * Build the shared `model.stub` variables.
     *
     * @return array<string, string>
     */
    protected function sharedVariables(CrudDefinition $definition): array
    {
        $vars = $definition->variables();
        $vars['fillableFields'] = $this->fields->fillableArray($definition->fields);
        $vars['migrationFields'] = $this->buildMigrationFields($definition);
        $vars['validationRules'] = $this->fields->validationRules($definition->fields);
        $vars['tsInterface'] = $this->fields->tsInterfaceBody($definition->fields);
        $vars['tsInterfaceBody'] = $this->fields->tsInterfaceBody($definition->fields);

        return $vars;
    }

    protected function buildMigrationFields(CrudDefinition $definition): string
    {
        $lines = [];
        foreach ($definition->fields as $field) {
            $lines[] = '            '.$this->fields->migrationFieldLine($field);
        }

        return implode("\n", $lines);
    }

    /**
     * @return string
     */
    protected function buildControllerUseStatements(CrudDefinition $definition): string
    {
        $use = [
            'use App\\Http\\Requests\\Store'.$definition->modelName.'Request;',
            'use App\\Http\\Requests\\Update'.$definition->modelName.'Request;',
        ];

        if ($definition->withService) {
            $use[] = 'use '.$definition->serviceNamespace.'\\'.$definition->modelName.'Service;';
        }
        if ($definition->withRepository) {
            $use[] = 'use '.$definition->repositoryContractNamespace.'\\'.$definition->modelName.'RepositoryInterface;';
        }

        return implode("\n", $use);
    }

    protected function buildControllerProperties(CrudDefinition $definition): string
    {
        $searchable = $this->collectSearchable($definition);
        $sortable = $this->collectSortable($definition);

        $props = [
            '    /** @var array<int, string> */',
            '    protected array $searchable = ['.implode(', ', array_map(fn ($f) => "'$f'", $searchable)).'];',
            '    /** @var array<int, string> */',
            '    protected array $sortable = ['.implode(', ', array_map(fn ($f) => "'$f'", $sortable)).'];',
        ];

        if ($definition->withService) {
            $props[] = '';
            $props[] = '    public function __construct(';
            $props[] = '        protected '.$definition->modelName.'Service $service,';
            $props[] = '    ) {}';
        }

        return implode("\n", $props);
    }

    protected function buildServiceCall(string $method, CrudDefinition $definition): string
    {
        if ($definition->withService) {
            $subject = match ($method) {
                'store' => 'this->service->store($request->validated())',
                'update' => 'this->service->update($'.$definition->modelVariable().', $request->validated())',
                'destroy' => 'this->service->destroy($'.$definition->modelVariable().')',
                default => '/* unknown */',
            };

            return $subject;
        }

        return match ($method) {
            'store' => $definition->modelName.'::create($request->validated())',
            'update' => $definition->modelVariable().'->update($request->validated())',
            'destroy' => $definition->modelVariable().'->delete()',
            default => '/* unknown */',
        };
    }

    /**
     * @return array<int, string>
     */
    protected function collectSearchable(CrudDefinition $definition): array
    {
        $out = [];
        foreach ($definition->fields as $f) {
            if (! empty($f['searchable'])) {
                $out[] = $f['name'];
            }
        }

        return $out;
    }

    /**
     * @return array<int, string>
     */
    protected function collectSortable(CrudDefinition $definition): array
    {
        $out = ['id', 'created_at', 'updated_at'];
        foreach ($definition->fields as $f) {
            if (! empty($f['sortable'])) {
                $out[] = $f['name'];
            }
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    protected function serviceVariables(CrudDefinition $definition): array
    {
        $vars = $definition->variables();
        $vars['fillableFields'] = $this->fields->fillableArray($definition->fields);

        if ($definition->withRepository) {
            $vars['repositoryUseStatement'] = 'use '.$definition->repositoryContractNamespace.'\\'.$definition->modelName.'RepositoryInterface;';
            $vars['repositoryInterfaceImport'] = '';
            $vars['serviceConstructor'] = '        protected '.$definition->modelName.'RepositoryInterface $repository,';
            $vars['repositoryPaginate'] = '$this->repository->paginate';
            $vars['repositoryFindOrFail'] = '$this->repository->findOrFail';
            $vars['repositoryCreate'] = '$this->repository->create';
            $vars['repositoryUpdate'] = '$this->repository->update';
            $vars['repositoryDelete'] = '$this->repository->delete';
        } else {
            $vars['repositoryUseStatement'] = '';
            $vars['repositoryInterfaceImport'] = '';
            $vars['serviceConstructor'] = '';
            $vars['repositoryPaginate'] = $definition->modelName.'::query()->latest()->paginate';
            $vars['repositoryFindOrFail'] = $definition->modelName.'::findOrFail';
            $vars['repositoryCreate'] = $definition->modelName.'::create';
            $vars['repositoryUpdate'] = '$'.$definition->modelVariable().'->update';
            $vars['repositoryDelete'] = '$'.$definition->modelVariable().'->delete';
        }

        return $vars;
    }

    protected function generateService(CrudDefinition $definition, bool $force): array
    {
        $path = $definition->serviceNamespace.'\\'.$definition->modelName.'Service';
        $destination = app_path(str_replace('\\', '/', str_replace('App\\', '', $path))).'.php';

        return $this->makeShared(
            $definition,
            'service.stub',
            $destination,
            $this->serviceVariables($definition),
            $force,
        );
    }

    protected function generateRepository(CrudDefinition $definition, bool $force): array
    {
        $destination = app_path('Repositories/'.$definition->modelName.'Repository.php');

        return $this->makeShared(
            $definition,
            'repository.stub',
            $destination,
            $definition->variables(),
            $force,
        );
    }

    protected function generateRepositoryInterface(CrudDefinition $definition, bool $force): array
    {
        $destination = app_path('Repositories/Contracts/'.$definition->modelName.'RepositoryInterface.php');

        return $this->makeShared(
            $definition,
            'repository-interface.stub',
            $destination,
            $definition->variables(),
            $force,
        );
    }

    protected function bindRepository(CrudDefinition $definition): void
    {
        $provider = app_path('Providers/AppServiceProvider.php');
        if (! $this->files->exists($provider)) {
            return;
        }

        $content = $this->files->get($provider);
        $interface = $definition->repositoryContractNamespace.'\\'.$definition->modelName.'RepositoryInterface';
        $concrete = $definition->repositoryNamespace.'\\'.$definition->modelName.'Repository';
        $bindLine = "\$this->app->bind({$interface}::class, {$concrete}::class);";

        if (str_contains($content, $bindLine)) {
            return;
        }

        $content = preg_replace(
            '/(public function register\(\): void\s*\{)/s',
            "$1\n        {$bindLine}",
            $content,
            1
        );

        if (! str_contains($content, $bindLine)) {
            $content = preg_replace(
                '/(public function register\(\)\s*\{)/s',
                "$1\n        {$bindLine}\n",
                $content,
                1
            );
        }

        if (str_contains($content, $bindLine)) {
            $this->files->put($provider, $content);
        }
    }

    protected function makeMigration(CrudDefinition $definition, bool $force): array
    {
        $timestamp = now()->format('Y_m_d_His');
        $destination = database_path('migrations/'.$timestamp.'_create_'.$definition->tableName.'_table.php');

        return $this->makeShared(
            $definition,
            'migration.stub',
            $destination,
            ['migrationFields' => $this->buildMigrationFields($definition)],
            $force,
        );
    }

    abstract protected function generateFrontend(CrudDefinition $definition, bool $force): array;
}
