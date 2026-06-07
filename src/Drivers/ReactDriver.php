<?php

declare(strict_types=1);

namespace SajidUlIslam\CrudGenerator\Drivers;

use Illuminate\Support\Str;
use SajidUlIslam\CrudGenerator\CrudDefinition;
use SajidUlIslam\CrudGenerator\Enums\Stack;

class ReactDriver extends InertiaDriver
{
    public function getStackName(): string
    {
        return 'React (Inertia + shadcn/ui)';
    }

    public function getStackKey(): Stack
    {
        return Stack::React;
    }

    protected function resolveStackKey(): Stack
    {
        return Stack::React;
    }

    protected function generateFrontend(CrudDefinition $definition, bool $force): array
    {
        $writes = [];
        $pagesPath = resource_path('js/pages/'.$definition->modelNamePlural());

        $imports = $this->imports();
        $ts = $this->tsVars($definition);

        $vars = array_merge($definition->variables(), $ts, [
            'reactImports' => $imports,
            'tableHeaders' => $this->buildReactHeaders($definition),
            'tableCells' => $this->buildReactCells($definition),
            'formFields' => $this->buildReactFormFields($definition),
            'formInitialValues' => $this->buildReactInitialValues($definition),
            'showRows' => $this->buildReactShowRows($definition),
        ]);

        foreach (['Index', 'Create', 'Edit', 'Show'] as $page) {
            $writes[strtolower($page)] = $this->makeFile(
                $definition,
                $page.'.tsx.stub',
                $pagesPath.'/'.$page.'.tsx',
                $vars,
                $force,
            );
        }

        $typesPath = resource_path('js/types/'.Str::kebab($definition->modelNamePlural()).'.ts');
        $writes['types'] = $this->makeFile(
            $definition,
            'type.ts.stub',
            $typesPath,
            array_merge($definition->variables(), $ts),
            $force,
        );

        return $writes;
    }

    /**
     * @return array<string, string>
     */
    protected function tsVars(CrudDefinition $definition): array
    {
        return [
            'tsInterface' => $this->fields->tsInterfaceBody($definition->fields),
            'tsInterfaceBody' => $this->fields->tsInterfaceBody($definition->fields),
        ];
    }

    protected function imports(): string
    {
        $cfg = app(\SajidUlIslam\CrudGenerator\Services\ConfigLoader::class);
        $imports = $cfg->get('imports');

        if (! is_array($imports) || empty($imports)) {
            return <<<'TXT'
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/table';
TXT;
        }

        $out = [];
        foreach ($imports as $name => $path) {
            $out[] = "import { {$name} } from '{$path}';";
        }

        return implode("\n", $out);
    }

    protected function buildReactHeaders(CrudDefinition $definition): string
    {
        $lines = [];
        foreach ($definition->fields as $field) {
            $name = $field['name'] ?? null;
            if (! is_string($name) || $name === '' || $name === 'id') {
                continue;
            }
            $lines[] = '                <TableHead>'.Str::title(str_replace('_', ' ', $name)).'</TableHead>';
        }

        return implode("\n", $lines);
    }

    protected function buildReactCells(CrudDefinition $definition): string
    {
        $lines = [];
        foreach ($definition->fields as $field) {
            $name = $field['name'] ?? null;
            if (! is_string($name) || $name === '' || $name === 'id') {
                continue;
            }
            $lines[] = "                        <TableCell>{item.{$name}}</TableCell>";
        }

        return implode("\n", $lines);
    }

    protected function buildReactFormFields(CrudDefinition $definition): string
    {
        return $this->fields->reactFormFields($definition->fields, 'data');
    }

    protected function buildReactInitialValues(CrudDefinition $definition): string
    {
        $lines = [];
        foreach ($definition->fields as $field) {
            $name = $field['name'] ?? null;
            $type = $field['type'] ?? 'string';
            if (! is_string($name) || $name === '' || $name === 'id') {
                continue;
            }
            $default = match (true) {
                in_array($type, ['boolean'], true) => 'false',
                in_array($type, ['integer', 'bigInteger', 'decimal', 'float', 'double'], true) => '0',
                default => "''",
            };
            $lines[] = "        {$name}: {$default},";
        }

        return implode("\n", $lines);
    }

    protected function buildReactShowRows(CrudDefinition $definition): string
    {
        $lines = [];
        foreach ($definition->fields as $field) {
            $name = $field['name'] ?? null;
            if (! is_string($name) || $name === '' || $name === 'id') {
                continue;
            }
            $label = Str::title(str_replace('_', ' ', $name));
            $lines[] = <<<JSX
                <div className="flex justify-between p-3">
                    <span className="font-medium">{$label}</span>
                    <span>{{$this->$name} }</span>
                </div>
JSX;
        }

        return implode("\n", $lines);
    }
}
