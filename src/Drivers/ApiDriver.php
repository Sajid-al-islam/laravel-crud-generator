<?php

declare(strict_types=1);

namespace SajidUlIslam\CrudGenerator\Drivers;

use SajidUlIslam\CrudGenerator\CrudDefinition;
use SajidUlIslam\CrudGenerator\Enums\Stack;

class ApiDriver extends AbstractDriver
{
    public function getStackName(): string
    {
        return 'API only (JSON)';
    }

    public function getStackKey(): Stack
    {
        return Stack::Api;
    }

    protected function resolveStackKey(): Stack
    {
        return Stack::Api;
    }

    public function generate(CrudDefinition $definition): array
    {
        $force = (bool) ($definition->options['force'] ?? false);
        $writes = [];

        $vars = $this->variables($definition);

        $writes['controller'] = $this->makeFile(
            $definition,
            'controller.stub',
            app_path('Http/Controllers/Api/'.$definition->modelName.'Controller.php'),
            $vars,
            $force,
        );

        $writes['model'] = $this->makeShared(
            $definition,
            'model.stub',
            app_path('Models/'.$definition->modelName.'.php'),
            $vars,
            $force,
        );

        if ($definition->options['with_migration'] ?? true) {
            $writes['migration'] = $this->makeMigration($definition, $force);
        }

        $writes['store_request'] = $this->makeFile(
            $definition,
            'store-request.stub',
            app_path('Http/Requests/Store'.$definition->modelName.'Request.php'),
            $vars,
            $force,
        );

        $writes['update_request'] = $this->makeFile(
            $definition,
            'update-request.stub',
            app_path('Http/Requests/Update'.$definition->modelName.'Request.php'),
            $vars,
            $force,
        );

        $writes['resource'] = $this->makeFile(
            $definition,
            'resource.stub',
            app_path('Http/Resources/'.$definition->modelName.'Resource.php'),
            $vars,
            $force,
        );

        $writes['resource_collection'] = $this->makeFile(
            $definition,
            'resource-collection.stub',
            app_path('Http/Resources/'.$definition->modelName.'Collection.php'),
            $vars,
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

        $this->ensureApiBaseController();

        if ($definition->options['register_routes'] ?? true) {
            $routesPath = $this->routes->register($definition);
            $writes['routes'] = [
                'path' => $routesPath ?? '',
                'status' => $routesPath ? 'modified' : 'skipped',
            ];
        }

        return $writes;
    }

    /**
     * @return array<string, string>
     */
    protected function variables(CrudDefinition $definition): array
    {
        $vars = $definition->variables();
        $vars['namespace'] = 'App\\Http';
        $vars['resourceNamespace'] = 'App\\Http\\Resources';
        $vars['fillableFields'] = $this->fields->fillableArray($definition->fields);
        $vars['migrationFields'] = $this->buildMigrationFields($definition);
        $vars['validationRules'] = $this->fields->validationRules($definition->fields);
        $vars['resourceFields'] = $this->buildResourceFields($definition);
        $vars['useStatements'] = $this->buildUseStatements($definition);
        $vars['controllerProperties'] = $this->buildControllerProperties($definition);
        $vars['serviceStoreLine'] = $this->buildServiceCall('store', $definition);
        $vars['serviceUpdateLine'] = $this->buildServiceCall('update', $definition);
        $vars['serviceDestroyLine'] = $this->buildServiceCall('destroy', $definition);

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

    protected function buildResourceFields(CrudDefinition $definition): string
    {
        $lines = [];
        foreach ($definition->fields as $field) {
            $name = $field['name'] ?? null;
            if (! is_string($name) || $name === '' || $name === 'id') {
                continue;
            }
            $lines[] = "            '{$name}' => \$this->{$name},";
        }

        return implode("\n", $lines);
    }

    protected function buildUseStatements(CrudDefinition $definition): string
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
        if (! $definition->withService) {
            return match ($method) {
                'store' => $definition->modelName.'::create($request->validated())',
                'update' => $definition->modelVariable().'->update($request->validated())',
                'destroy' => $definition->modelVariable().'->delete()',
                default => '/* unknown */',
            };
        }

        return match ($method) {
            'store' => 'this->service->store($request->validated())',
            'update' => 'this->service->update($'.$definition->modelVariable().', $request->validated())',
            'destroy' => 'this->service->destroy($'.$definition->modelVariable().')',
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

    protected function ensureApiBaseController(): void
    {
        $path = app_path('Http/Controllers/Api/Controller.php');
        if ($this->files->exists($path)) {
            return;
        }

        if (! $this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0755, true);
        }

        $this->files->put($path, <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
PHP);
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
        $path = str_replace('\\', '/', str_replace('App\\', '', $definition->serviceNamespace.'\\'.$definition->modelName.'Service')).'.php';
        $destination = app_path($path);

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
        return $this->makeShared(
            $definition,
            'repository.stub',
            app_path('Repositories/'.$definition->modelName.'Repository.php'),
            $definition->variables(),
            $force,
        );
    }

    protected function generateRepositoryInterface(CrudDefinition $definition, bool $force): array
    {
        return $this->makeShared(
            $definition,
            'repository-interface.stub',
            app_path('Repositories/Contracts/'.$definition->modelName.'RepositoryInterface.php'),
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
}
