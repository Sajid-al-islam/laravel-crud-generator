<?php

declare(strict_types=1);

namespace SajidUlIslam\CrudGenerator\Drivers;

use Illuminate\Support\Str;
use SajidUlIslam\CrudGenerator\CrudDefinition;
use SajidUlIslam\CrudGenerator\Enums\Stack;

class NovaDriver extends AbstractDriver
{
    public function getStackName(): string
    {
        return 'Laravel Nova';
    }

    public function getStackKey(): Stack
    {
        return Stack::Nova;
    }

    protected function resolveStackKey(): Stack
    {
        return Stack::Nova;
    }

    public function getRequiredDependencies(): array
    {
        return [\Laravel\Nova\Resource::class];
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

        $writes['nova_resource'] = $this->makeFile(
            $definition,
            'resource.stub',
            app_path('Nova/'.$definition->modelName.'.php'),
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
        $vars['fieldImports'] = $this->buildNovaFieldImports($definition);
        $vars['novaFieldsBody'] = $this->buildNovaFieldsBody($definition);
        $vars['novaSearchable'] = $this->buildNovaSearchable($definition);

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

    protected function buildNovaFieldImports(CrudDefinition $definition): string
    {
        $types = ['ID', 'Text', 'Textarea', 'Number', 'Boolean', 'Date', 'DateTime', 'BelongsTo', 'Select'];
        return implode(', ', $types);
    }

    protected function buildNovaFieldsBody(CrudDefinition $definition): string
    {
        $lines = ["            ID::make()->sortable(),"];
        foreach ($this->fields->novaFields($definition) as $field) {
            $name = $field['name'];
            $type = $field['type'];
            $method = $field['method'];
            $related = $field['related'] ?? null;

            $line = match ($type) {
                'BelongsTo' => "            BelongsTo::make('".Str::title(str_replace('_', ' ', $name))."', '".Str::camel(Str::substr($name, 0, -3))."', \\App\\Nova\\{$related}::class),",
                'Textarea' => "            Textarea::make('".Str::title(str_replace('_', ' ', $name))."'),",
                'Number' => "            Number::make('".Str::title(str_replace('_', ' ', $name))."')->sortable(),",
                'Boolean' => "            Boolean::make('".Str::title(str_replace('_', ' ', $name))."'),",
                'Date' => "            Date::make('".Str::title(str_replace('_', ' ', $name))."'),",
                'DateTime' => "            DateTime::make('".Str::title(str_replace('_', ' ', $name))."'),",
                'Select' => "            Select::make('".Str::title(str_replace('_', ' ', $name))."')->options([]),",
                default => "            Text::make('".Str::title(str_replace('_', ' ', $name))."')->sortable(),",
            };
            $lines[] = $line;
        }
        $lines[] = "            DateTime::make('Created At')->onlyOnDetail(),";
        $lines[] = "            DateTime::make('Updated At')->onlyOnDetail(),";

        return implode("\n", $lines);
    }

    protected function buildNovaSearchable(CrudDefinition $definition): string
    {
        $names = ['id'];
        foreach ($definition->fields as $field) {
            $name = $field['name'] ?? null;
            if (! is_string($name) || $name === '' || $name === 'id') {
                continue;
            }
            if (! empty($field['searchable'])) {
                $names[] = $name;
            }
        }
        if (empty($names)) {
            return "        'id',";
        }

        $out = [];
        foreach ($names as $n) {
            $out[] = "        '{$n}',";
        }

        return implode("\n", $out);
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
