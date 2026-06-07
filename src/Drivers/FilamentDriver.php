<?php

declare(strict_types=1);

namespace SajidUlIslam\CrudGenerator\Drivers;

use SajidUlIslam\CrudGenerator\CrudDefinition;
use SajidUlIslam\CrudGenerator\Enums\Stack;

class FilamentDriver extends AbstractDriver
{
    public function getStackName(): string
    {
        return 'Laravel Filament';
    }

    public function getStackKey(): Stack
    {
        return Stack::Filament;
    }

    protected function resolveStackKey(): Stack
    {
        return Stack::Filament;
    }

    public function getRequiredDependencies(): array
    {
        return [\Filament\Resources\Resource::class];
    }

    public function generate(CrudDefinition $definition): array
    {
        $this->assertDependencies();

        $force = (bool) ($definition->options['force'] ?? false);
        $writes = [];

        $vars = $this->variables($definition);

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

        $base = app_path('Filament/Resources/'.$definition->modelName.'Resource');
        if (! $this->files->isDirectory($base)) {
            $this->files->makeDirectory($base.'/Pages', 0755, true);
        }

        $writes['resource'] = $this->makeFile(
            $definition,
            'resource.stub',
            $base.'.php',
            $vars,
            $force,
        );

        $writes['list_page'] = $this->makeFile(
            $definition,
            'ListPages.stub',
            $base.'/Pages/List'.$definition->modelNamePlural().'.php',
            $vars,
            $force,
        );

        $writes['create_page'] = $this->makeFile(
            $definition,
            'CreatePage.stub',
            $base.'/Pages/Create'.$definition->modelName.'.php',
            $vars,
            $force,
        );

        $writes['edit_page'] = $this->makeFile(
            $definition,
            'EditPage.stub',
            $base.'/Pages/Edit'.$definition->modelName.'.php',
            $vars,
            $force,
        );

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
        $vars['filamentFormFields'] = $this->fields->filamentFormFields($definition->fields);
        $vars['filamentTableColumns'] = $this->fields->filamentTableColumns($definition->fields);

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
}
