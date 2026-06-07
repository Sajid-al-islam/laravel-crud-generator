<?php

declare(strict_types=1);

namespace SajidUlIslam\CrudGenerator\Drivers;

use Illuminate\Support\Str;
use SajidUlIslam\CrudGenerator\CrudDefinition;
use SajidUlIslam\CrudGenerator\Enums\Stack;

class LivewireDriver extends AbstractDriver
{
    public function getStackName(): string
    {
        return 'Livewire (Flux UI)';
    }

    public function getStackKey(): Stack
    {
        return Stack::Livewire;
    }

    protected function resolveStackKey(): Stack
    {
        return Stack::Livewire;
    }

    public function getRequiredDependencies(): array
    {
        return [
            \Livewire\Component::class,
        ];
    }

    public function generate(CrudDefinition $definition): array
    {
        $this->assertDependencies();

        $force = (bool) ($definition->options['force'] ?? false);
        $writes = [];

        $vars = $this->variables($definition);

        $model = $definition->modelName;
        $plural = $definition->modelNamePlural();
        $mv = $definition->modelVariable();
        $base = app_path('Livewire/'.$plural);

        // Livewire components
        foreach (['Index', 'Create', 'Edit', 'Show'] as $component) {
            $writes[strtolower($component)] = $this->makeFile(
                $definition,
                $component.'.php.stub',
                $base.'/'.$component.'.php',
                $vars,
                $force,
            );
        }

        // Blade views
        $views = ['index', 'create', 'edit', 'show'];
        $viewPath = resource_path('views/livewire/'.$definition->routePrefix());
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

        // Model + migration + service + repository
        $writes['model'] = $this->makeShared(
            $definition,
            'model.stub',
            app_path('Models/'.$model.'.php'),
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

        return $writes;
    }

    /**
     * @return array<string, string>
     */
    protected function variables(CrudDefinition $definition): array
    {
        $vars = $definition->variables();
        $vars['fillableFields'] = $this->fields->fillableArray($definition->fields);
        $vars['migrationFields'] = $this->buildMigrationFields($definition);
        $vars['validationRules'] = $this->fields->validationRules($definition->fields);
        $vars['useStatements'] = $this->buildUseStatements($definition);
        $vars['formProperties'] = $this->buildFormProperties($definition);
        $vars['formMount'] = $this->buildFormMount($definition);
        $vars['searchableFieldsArray'] = $this->buildArray($this->collectSearchable($definition));
        $vars['sortableFieldsArray'] = $this->buildArray($this->collectSortable($definition));
        $vars['tableColumns'] = $this->fields->livewireTableColumns($definition->fields);
        $vars['tableCells'] = $this->buildTableCells($definition);
        $vars['formFields'] = $this->fields->livewireFormFields($definition->fields);
        $vars['showFields'] = $this->buildShowFields($definition);

        $vars['serviceFindLine'] = $definition->modelName.'::find($id)';
        $vars['serviceDeleteLine'] = $definition->withService
            ? '$this->service->destroy('.$definition->modelVariable().')'
            : $definition->modelVariable().'->delete()';
        $vars['serviceStore'] = $definition->withService
            ? '$this->service->store($this->form->all())'
            : $definition->modelName.'::create($this->form->all())';
        $vars['serviceUpdate'] = $definition->withService
            ? '$this->service->update($this->'.$definition->modelVariable().', $this->form->all())'
            : '$this->'.$definition->modelVariable().'->update($this->form->all())';

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
        $use = [];
        if ($definition->withService) {
            $use[] = 'use '.$definition->serviceNamespace.'\\'.$definition->modelName.'Service;';
        }
        if ($definition->withRepository) {
            $use[] = 'use '.$definition->repositoryContractNamespace.'\\'.$definition->modelName.'RepositoryInterface;';
        }

        return implode("\n", $use);
    }

    protected function buildFormProperties(CrudDefinition $definition): string
    {
        $lines = [];
        foreach ($definition->fields as $field) {
            $name = $field['name'] ?? null;
            $type = $field['type'] ?? 'string';
            $validation = $field['validation'] ?? 'nullable';
            if (! is_string($name) || $name === '' || $name === 'id') {
                continue;
            }
            $default = match (true) {
                in_array($type, ['boolean'], true) => 'false',
                in_array($type, ['integer', 'bigInteger', 'decimal', 'float', 'double'], true) => '0',
                default => "''",
            };
            $lines[] = "    #[Validate('{$validation}')]";
            $lines[] = "    public \${$name} = {$default};";
            $lines[] = '';
        }

        return rtrim(implode("\n", $lines));
    }

    protected function buildFormMount(CrudDefinition $definition): string
    {
        $lines = [];
        foreach ($definition->fields as $field) {
            $name = $field['name'] ?? null;
            if (! is_string($name) || $name === '' || $name === 'id') {
                continue;
            }
            $lines[] = "        \$this->{$name} = \$this->".$definition->modelVariable().'->'.$name.';';
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<int, string>  $items
     */
    protected function buildArray(array $items): string
    {
        if (empty($items)) {
            return '';
        }

        return implode(', ', array_map(fn ($f) => "'$f'", $items));
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

    protected function buildTableCells(CrudDefinition $definition): string
    {
        $lines = [];
        $mv = $definition->modelVariable();
        foreach ($definition->fields as $field) {
            $name = $field['name'] ?? null;
            if (! is_string($name) || $name === '' || $name === 'id') {
                continue;
            }
            $lines[] = "                    <flux:cell>{{ \${$mv}->{$name} }}</flux:cell>";
        }

        return implode("\n", $lines);
    }

    protected function buildShowFields(CrudDefinition $definition): string
    {
        $lines = [];
        $mv = $definition->modelVariable();
        foreach ($definition->fields as $field) {
            $name = $field['name'] ?? null;
            if (! is_string($name) || $name === '' || $name === 'id') {
                continue;
            }
            $label = Str::title(str_replace('_', ' ', $name));
            $lines[] = <<<TPL
        <div class="flex justify-between p-3">
            <span class="font-medium">{$label}</span>
            <span>{{ \${$mv}->{$name} }}</span>
        </div>
TPL;
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
