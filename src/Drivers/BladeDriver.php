<?php

declare(strict_types=1);

namespace SajidUlIslam\CrudGenerator\Drivers;

use Illuminate\Support\Str;
use SajidUlIslam\CrudGenerator\CrudDefinition;
use SajidUlIslam\CrudGenerator\Enums\Stack;

class BladeDriver extends AbstractDriver
{
    public function getStackName(): string
    {
        return 'Blade (traditional)';
    }

    public function getStackKey(): Stack
    {
        return Stack::Blade;
    }

    protected function resolveStackKey(): Stack
    {
        return Stack::Blade;
    }

    public function generate(CrudDefinition $definition): array
    {
        $force = (bool) ($definition->options['force'] ?? false);
        $layout = (string) ($definition->options['layout'] ?? 'layouts.app');
        $writes = [];

        $vars = $this->variables($definition, $layout);

        $writes['controller'] = $this->makeFile(
            $definition,
            'controller.stub',
            app_path('Http/Controllers/'.$definition->modelName.'Controller.php'),
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

        if ($definition->withService) {
            $writes['service'] = $this->generateService($definition, $force);
        }
        if ($definition->withRepository) {
            $writes['repository_interface'] = $this->generateRepositoryInterface($definition, $force);
            $writes['repository'] = $this->generateRepository($definition, $force);
            $this->bindRepository($definition);
        }

        $views = ['index', 'create', 'edit', 'show'];
        $viewPath = resource_path('views/'.$definition->routePrefix());
        $generated = [];
        foreach ($views as $view) {
            $generated[] = $this->makeFile(
                $definition,
                $view.'.blade.stub',
                $viewPath.'/'.$view.'.blade.php',
                $vars,
                $force,
            );
        }
        $writes['views'] = $generated;

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
    protected function variables(CrudDefinition $definition, string $layout = 'layouts.app'): array
    {
        $vars = $definition->variables();
        $vars['layout'] = $layout;
        $vars['fillableFields'] = $this->fields->fillableArray($definition->fields);
        $vars['migrationFields'] = $this->buildMigrationFields($definition);
        $vars['validationRules'] = $this->fields->validationRules($definition->fields);
        $vars['useStatements'] = $this->buildUseStatements($definition);
        $vars['controllerProperties'] = $this->buildControllerProperties($definition);
        $vars['serviceStore'] = $this->buildServiceCall('store', $definition);
        $vars['serviceUpdate'] = $this->buildServiceCall('update', $definition);
        $vars['serviceDestroy'] = $this->buildServiceCall('destroy', $definition);
        $vars['tableHeaders'] = $this->buildTableHeaders($definition);
        $vars['tableData'] = $this->buildTableData($definition);
        $vars['formFields'] = $this->buildFormFields($definition);
        $vars['showFields'] = $this->buildShowFields($definition);

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

    protected function buildTableHeaders(CrudDefinition $definition): string
    {
        $lines = [];
        foreach ($definition->fields as $field) {
            $name = $field['name'] ?? null;
            if (! is_string($name) || $name === '' || $name === 'id') {
                continue;
            }
            $label = Str::title(str_replace('_', ' ', $name));
            $lines[] = "                <th>{$label}</th>";
        }

        return implode("\n", $lines);
    }

    protected function buildTableData(CrudDefinition $definition): string
    {
        $lines = [];
        foreach ($definition->fields as $field) {
            $name = $field['name'] ?? null;
            if (! is_string($name) || $name === '' || $name === 'id') {
                continue;
            }
            $mv = $definition->modelVariable();
            $lines[] = "                    <td>{{ \${$mv}->{$name} }}</td>";
        }

        return implode("\n", $lines);
    }

    protected function buildFormFields(CrudDefinition $definition): string
    {
        $lines = [];
        foreach ($definition->fields as $field) {
            $name = $field['name'] ?? null;
            $type = $field['type'] ?? 'string';
            if (! is_string($name) || $name === '' || $name === 'id') {
                continue;
            }
            $label = Str::title(str_replace('_', ' ', $name));
            $mv = $definition->modelVariable();

            $input = match (true) {
                in_array($type, ['text', 'longText'], true) => "    <textarea class=\"form-control\" name=\"{$name}\" id=\"{$name}\">{{ old('{$name}', \${$mv}->{$name} ?? '') }}</textarea>",
                in_array($type, ['boolean'], true) => "    <select class=\"form-control\" name=\"{$name}\" id=\"{$name}\">\n        <option value=\"1\" {{ old('{$name}', \${$mv}->{$name} ?? 0) == 1 ? 'selected' : '' }}>Yes</option>\n        <option value=\"0\" {{ old('{$name}', \${$mv}->{$name} ?? 0) == 0 ? 'selected' : '' }}>No</option>\n    </select>",
                in_array($type, ['date'], true) => "    <input type=\"date\" class=\"form-control\" name=\"{$name}\" id=\"{$name}\" value=\"{{ old('{$name}', \${$mv}->{$name} ?? '') }}\">",
                in_array($type, ['datetime', 'timestamp', 'dateTime'], true) => "    <input type=\"datetime-local\" class=\"form-control\" name=\"{$name}\" id=\"{$name}\" value=\"{{ old('{$name}', optional(\${$mv}->{$name})?->format('Y-m-d\\TH:i')) }}\">",
                in_array($type, ['integer', 'bigInteger', 'decimal', 'float', 'double'], true) => "    <input type=\"number\" step=\"any\" class=\"form-control\" name=\"{$name}\" id=\"{$name}\" value=\"{{ old('{$name}', \${$mv}->{$name} ?? '') }}\">",
                default => "    <input type=\"text\" class=\"form-control\" name=\"{$name}\" id=\"{$name}\" value=\"{{ old('{$name}', \${$mv}->{$name} ?? '') }}\">",
            };

            $lines[] = "    <div class=\"mb-3\">\n        <label for=\"{$name}\" class=\"form-label\">{$label}</label>\n{$input}\n    </div>";
        }

        return implode("\n\n", $lines);
    }

    protected function buildShowFields(CrudDefinition $definition): string
    {
        $lines = [];
        foreach ($definition->fields as $field) {
            $name = $field['name'] ?? null;
            if (! is_string($name) || $name === '' || $name === 'id') {
                continue;
            }
            $label = Str::title(str_replace('_', ' ', $name));
            $mv = $definition->modelVariable();
            $lines[] = "    <div class=\"row mb-2\">\n        <div class=\"col-md-3\"><strong>{$label}:</strong></div>\n        <div class=\"col-md-9\">{{ \${$mv}->{$name} }}</div>\n    </div>";
        }

        return implode("\n", $lines);
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
