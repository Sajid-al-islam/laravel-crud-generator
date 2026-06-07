<?php

declare(strict_types=1);

namespace SajidUlIslam\CrudGenerator\Services;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use SajidUlIslam\CrudGenerator\CrudDefinition;
use SajidUlIslam\CrudGenerator\Enums\Stack;

class FieldParser
{
    /**
     * Map a field type to a TypeScript primitive.
     *
     * @return string
     */
    public function typescriptType(string $type): string
    {
        return match (true) {
            in_array($type, ['integer', 'bigInteger', 'smallInteger', 'tinyInteger', 'unsignedBigInteger', 'unsignedInteger'], true) => 'number',
            in_array($type, ['decimal', 'float', 'double', 'real'], true) => 'number',
            in_array($type, ['boolean', 'bool'], true) => 'boolean',
            in_array($type, ['date', 'datetime', 'timestamp', 'dateTime'], true) => 'string',
            in_array($type, ['json', 'jsonb'], true) => 'Record<string, unknown>',
            default => 'string',
        };
    }

    /**
     * Build a TypeScript interface body from a field array.
     */
    public function tsInterfaceBody(array $fields): string
    {
        $lines = ['    id: number;'];
        foreach ($fields as $field) {
            $name = $field['name'] ?? null;
            $type = $field['type'] ?? 'string';
            if (! is_string($name) || $name === '' || $name === 'id') {
                continue;
            }
            $nullable = (bool) ($field['nullable'] ?? false);
            $ts = $this->typescriptType($type);
            $suffix = $nullable ? ' | null' : '';
            $lines[] = "    {$name}{$suffix}: {$ts};";
        }
        $lines[] = "    created_at: string;";
        $lines[] = "    updated_at: string;";

        return implode("\n", $lines);
    }

    /**
     * Build a Laravel migration `$table->...` line.
     */
    public function migrationFieldLine(array $field): string
    {
        $name = $field['name'] ?? '';
        $type = $field['type'] ?? 'string';
        $nullable = (bool) ($field['nullable'] ?? false);

        $column = match ($type) {
            'string' => "string('{$name}')",
            'text', 'longText' => "text('{$name}')",
            'integer', 'bigInteger' => ($type === 'bigInteger' ? "bigInteger" : "integer")."('{$name}')",
            'boolean' => "boolean('{$name}')",
            'date' => "date('{$name}')",
            'datetime', 'dateTime', 'timestamp' => "dateTime('{$name}')",
            'decimal' => "decimal('{$name}', 10, 2)",
            'float' => "float('{$name}')",
            'json' => "json('{$name}')",
            'email' => "string('{$name}')",
            default => "string('{$name}')",
        };

        $nullableSuffix = $nullable ? '->nullable()' : '';
        $defaultSuffix = isset($field['default']) ? "->default('".addslashes((string) $field['default'])."')" : '';

        return "            \$table->{$column}{$nullableSuffix}{$defaultSuffix};";
    }

    /**
     * Build a `fillable` list, one per line.
     */
    public function fillableArray(array $fields): string
    {
        $names = array_map(fn ($f) => "'".($f['name'] ?? '')."'", $fields);
        $names = array_values(array_filter($names, fn ($n) => $n !== "''"));

        if (empty($names)) {
            return '';
        }

        return implode(",\n        ", $names);
    }

    /**
     * Build a `validationRules` PHP array literal.
     */
    public function validationRules(array $fields): string
    {
        $rules = [];
        foreach ($fields as $field) {
            $name = $field['name'] ?? null;
            if (! is_string($name) || $name === '' || $name === 'id') {
                continue;
            }
            $validation = $field['validation'] ?? null;
            if (is_string($validation) && $validation !== '') {
                $rules[] = "            '{$name}' => '{$validation}',";
            } else {
                $rules[] = "            '{$name}' => 'nullable',";
            }
        }

        return implode("\n", $rules);
    }

    /**
     * Detect foreign keys (fields ending in `_id`) and emit relation hints.
     *
     * @return array<int, array<string, string>>
     */
    public function detectRelations(array $fields): array
    {
        $relations = [];
        foreach ($fields as $field) {
            $name = $field['name'] ?? '';
            if (is_string($name) && Str::endsWith($name, '_id')) {
                $related = Str::studly(Str::substr($name, 0, -3));
                $relations[] = [
                    'name' => $name,
                    'type' => 'belongsTo',
                    'related' => $related,
                    'method' => Str::camel(Str::substr($name, 0, -3)),
                ];
            }
        }

        return $relations;
    }

    /**
     * @return array<int, array{name:string,type:string,method:string,related:string}>
     */
    public function novaFields(CrudDefinition $definition): array
    {
        $out = [];
        foreach ($definition->fields as $field) {
            $name = $field['name'] ?? null;
            $type = $field['type'] ?? 'string';
            if (! is_string($name) || $name === '' || $name === 'id') {
                continue;
            }
            $title = Str::title(str_replace('_', ' ', $name));
            $rules = $field['validation'] ?? '';

            if (Str::endsWith($name, '_id')) {
                $related = Str::studly(Str::substr($name, 0, -3));
                $out[] = [
                    'name' => $name,
                    'type' => 'BelongsTo',
                    'method' => Str::camel(Str::substr($name, 0, -3)),
                    'related' => $related,
                ];
                continue;
            }

            $method = match (true) {
                in_array($type, ['text', 'longText'], true) => 'Textarea',
                in_array($type, ['integer', 'bigInteger'], true) => 'Number',
                in_array($type, ['boolean'], true) => 'Boolean',
                in_array($type, ['date'], true) => 'Date',
                in_array($type, ['datetime', 'timestamp', 'dateTime'], true) => 'DateTime',
                in_array($type, ['decimal', 'float', 'double'], true) => 'Number',
                in_array($type, ['enum'], true) => 'Select',
                default => 'Text',
            };

            $out[] = [
                'name' => $name,
                'type' => $method,
                'method' => $title,
                'related' => $title,
            ];
        }

        return $out;
    }

    public function filamentFormFields(array $fields): string
    {
        $lines = [];
        foreach ($fields as $field) {
            $name = $field['name'] ?? null;
            $type = $field['type'] ?? 'string';
            if (! is_string($name) || $name === '' || $name === 'id') {
                continue;
            }
            $title = Str::title(str_replace('_', ' ', $name));
            $required = isset($field['validation']) && is_string($field['validation']) && str_contains($field['validation'], 'required');

            $line = match (true) {
                Str::endsWith($name, '_id') => "                        Select::make('{$title}')\n                            ->relationship('".Str::camel(Str::substr($name, 0, -3))."', 'name')\n                            ->required(),",
                in_array($type, ['text', 'longText'], true) => "                        Textarea::make('{$title}'),",
                in_array($type, ['boolean'], true) => "                        Toggle::make('{$title}'),",
                in_array($type, ['date'], true) => "                        DatePicker::make('{$title}'),",
                in_array($type, ['datetime', 'timestamp', 'dateTime'], true) => "                        DateTimePicker::make('{$title}'),",
                in_array($type, ['integer', 'bigInteger'], true) => "                        TextInput::make('{$title}')->numeric(),",
                in_array($type, ['enum'], true) => "                        Select::make('{$title}')->options([]),",
                in_array($type, ['decimal', 'float', 'double'], true) => "                        TextInput::make('{$title}')->numeric()->step(0.01),",
                default => "                        TextInput::make('{$title}')".($required ? "->required()" : '').',',
            };

            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    public function filamentTableColumns(array $fields): string
    {
        $lines = [];
        foreach ($fields as $field) {
            $name = $field['name'] ?? null;
            $type = $field['type'] ?? 'string';
            if (! is_string($name) || $name === '' || $name === 'id') {
                continue;
            }
            $title = Str::title(str_replace('_', ' ', $name));

            $line = match (true) {
                Str::endsWith($name, '_id') => "                        TextColumn::make('".Str::camel(Str::substr($name, 0, -3)).".name'),",
                in_array($type, ['text', 'longText'], true) => "                        TextColumn::make('{$title}')->limit(50),",
                in_array($type, ['boolean'], true) => "                        IconColumn::make('{$title}')->boolean(),",
                in_array($type, ['date'], true) => "                        TextColumn::make('{$title}')->date()->sortable(),",
                in_array($type, ['datetime', 'timestamp', 'dateTime'], true) => "                        TextColumn::make('{$title}')->dateTime()->sortable(),",
                in_array($type, ['integer', 'bigInteger'], true) => "                        TextColumn::make('{$title}')->sortable(),",
                in_array($type, ['enum'], true) => "                        TextColumn::make('{$title}')->badge(),",
                default => "                        TextColumn::make('{$title}')->sortable()->searchable(),",
            };

            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    public function livewireFormFields(array $fields): string
    {
        $lines = [];
        foreach ($fields as $field) {
            $name = $field['name'] ?? null;
            $type = $field['type'] ?? 'string';
            if (! is_string($name) || $name === '' || $name === 'id') {
                continue;
            }

            $line = match (true) {
                in_array($type, ['text', 'longText'], true) => "        <flux:textarea wire:model=\"form.{$name}\" label=\"".Str::title(str_replace('_', ' ', $name))."\" />",
                in_array($type, ['boolean'], true) => "        <flux:switch wire:model=\"form.{$name}\" label=\"".Str::title(str_replace('_', ' ', $name))."\" />",
                in_array($type, ['date'], true) => "        <flux:input type=\"date\" wire:model=\"form.{$name}\" label=\"".Str::title(str_replace('_', ' ', $name))."\" />",
                in_array($type, ['datetime', 'timestamp', 'dateTime'], true) => "        <flux:input type=\"datetime-local\" wire:model=\"form.{$name}\" label=\"".Str::title(str_replace('_', ' ', $name))."\" />",
                in_array($type, ['integer', 'bigInteger'], true) => "        <flux:input type=\"number\" wire:model=\"form.{$name}\" label=\"".Str::title(str_replace('_', ' ', $name))."\" />",
                default => "        <flux:input wire:model=\"form.{$name}\" label=\"".Str::title(str_replace('_', ' ', $name))."\" />",
            };

            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    public function livewireTableColumns(array $fields): string
    {
        $lines = [];
        foreach ($fields as $field) {
            $name = $field['name'] ?? null;
            $type = $field['type'] ?? 'string';
            if (! is_string($name) || $name === '' || $name === 'id') {
                continue;
            }
            $title = Str::title(str_replace('_', ' ', $name));

            $sortable = ! in_array($type, ['text', 'longText', 'json', 'jsonb'], true);

            $line = "        <flux:column".($sortable ? " sortable wire:click=\"\\$set('sortBy', '{$name}')\" wire:click=\"sortBy('{$name}')\"" : '').">{$title}</flux:column>";

            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    /**
     * Used by React/Vue/Svelte index templates to render a table row per field.
     */
    public function reactTableHeaders(array $fields): string
    {
        $headers = [];
        foreach ($fields as $field) {
            $name = $field['name'] ?? null;
            if (! is_string($name) || $name === '' || $name === 'id') {
                continue;
            }
            $headers[] = "                <TableHead>".Str::title(str_replace('_', ' ', $name)).'</TableHead>';
        }

        return implode("\n", $headers);
    }

    public function reactTableCells(array $fields, string $modelVariable): string
    {
        $cells = [];
        foreach ($fields as $field) {
            $name = $field['name'] ?? null;
            if (! is_string($name) || $name === '' || $name === 'id') {
                continue;
            }
            $cells[] = "                    <TableCell>{{$modelVariable}.{$name}}</TableCell>";
        }

        return implode("\n", $cells);
    }

    public function reactFormFields(array $fields, string $modelVariable = 'data'): string
    {
        $lines = [];
        foreach ($fields as $field) {
            $name = $field['name'] ?? null;
            $type = $field['type'] ?? 'string';
            if (! is_string($name) || $name === '' || $name === 'id') {
                continue;
            }
            $title = Str::title(str_replace('_', ' ', $name));

            $line = match (true) {
                in_array($type, ['text', 'longText'], true) => "            <div>\n                <Label htmlFor=\"{$name}\">{$title}</Label>\n                <Textarea\n                    id=\"{$name}\"\n                    value={data.{$name}}\n                    onChange={(e) => setData('{$name}', e.target.value)}\n                />\n                {errors.{$name} && <p className=\"text-sm text-red-500\">{errors.{$name}}</p>}\n            </div>",
                in_array($type, ['boolean'], true) => "            <div className=\"flex items-center gap-2\">\n                <Switch\n                    id=\"{$name}\"\n                    checked={data.{$name}}\n                    onCheckedChange={(checked) => setData('{$name}', checked)}\n                />\n                <Label htmlFor=\"{$name}\">{$title}</Label>\n            </div>",
                default => "            <div>\n                <Label htmlFor=\"{$name}\">{$title}</Label>\n                <Input\n                    id=\"{$name}\"\n                    value={data.{$name}}\n                    onChange={(e) => setData('{$name}', e.target.value)}\n                />\n                {errors.{$name} && <p className=\"text-sm text-red-500\">{errors.{$name}}</p>}\n            </div>",
            };

            $lines[] = $line;
        }

        return implode("\n\n", $lines);
    }

    public function vueFormFields(array $fields, string $modelVariable = 'form'): string
    {
        $lines = [];
        foreach ($fields as $field) {
            $name = $field['name'] ?? null;
            $type = $field['type'] ?? 'string';
            if (! is_string($name) || $name === '' || $name === 'id') {
                continue;
            }
            $title = Str::title(str_replace('_', ' ', $name));

            $line = match (true) {
                in_array($type, ['text', 'longText'], true) => "            <UiVeeField label=\"{$title}\" name=\"{$name}\">\n                <UiTextarea v-model=\"form.{$name}\" />\n            </UiVeeField>",
                in_array($type, ['boolean'], true) => "            <UiVeeField label=\"{$title}\" name=\"{$name}\">\n                <UiSwitch v-model=\"form.{$name}\" />\n            </UiVeeField>",
                default => "            <UiVeeField label=\"{$title}\" name=\"{$name}\">\n                <UiInput v-model=\"form.{$name}\" />\n            </UiVeeField>",
            };

            $lines[] = $line;
        }

        return implode("\n\n", $lines);
    }

    public function svelteFormFields(array $fields): string
    {
        $lines = [];
        foreach ($fields as $field) {
            $name = $field['name'] ?? null;
            $type = $field['type'] ?? 'string';
            if (! is_string($name) || $name === '' || $name === 'id') {
                continue;
            }
            $title = Str::title(str_replace('_', ' ', $name));

            $line = <<<SVELTE
                <Form.Field name="$name">
                    <Form.Control>
                        <Form.Label>$title</Form.Label>
                        <Textarea bind:value={\$form.$name} />
                    </Form.Control>
                    <Form.Description />
                    <Form.FieldErrors />
                </Form.Field>
            SVELTE;

            $lines[] = $line;
        }

        return implode("\n\n", $lines);
    }
}
