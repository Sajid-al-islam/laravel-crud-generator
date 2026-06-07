<?php

declare(strict_types=1);

namespace SajidUlIslam\CrudGenerator\Drivers;

use Illuminate\Support\Str;
use SajidUlIslam\CrudGenerator\CrudDefinition;
use SajidUlIslam\CrudGenerator\Enums\Stack;

class VueDriver extends InertiaDriver
{
    public function getStackName(): string
    {
        return 'Vue (Inertia + shadcn-vue)';
    }

    public function getStackKey(): Stack
    {
        return Stack::Vue;
    }

    protected function resolveStackKey(): Stack
    {
        return Stack::Vue;
    }

    protected function generateFrontend(CrudDefinition $definition, bool $force): array
    {
        $writes = [];
        $pagesPath = resource_path('js/pages/'.$definition->modelNamePlural());

        $ts = $this->tsVars($definition);
        $vars = array_merge($definition->variables(), $ts, [
            'vueImports' => $this->imports(),
            'tableHeaders' => $this->buildVueHeaders($definition),
            'tableCells' => $this->buildVueCells($definition),
            'formFields' => $this->fields->vueFormFields($definition->fields),
            'formInitialValues' => $this->buildVueInitialValues($definition),
            'showRows' => $this->buildVueShowRows($definition),
            'metaCurrentPage' => '{{ '.$definition->modelPluralVariable().'.meta.current_page }}',
            'metaLastPage' => '{{ '.$definition->modelPluralVariable().'.meta.last_page }}',
        ]);

        foreach (['Index', 'Create', 'Edit', 'Show'] as $page) {
            $writes[strtolower($page)] = $this->makeFile(
                $definition,
                $page.'.vue.stub',
                $pagesPath.'/'.$page.'.vue',
                $vars,
                $force,
            );
        }

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
import { Button as UiButton } from '@/components/ui/button';
import { Input as UiInput } from '@/components/ui/input';
import { Textarea as UiTextarea } from '@/components/ui/textarea';
import { Switch as UiSwitch } from '@/components/ui/switch';
import { Table as UiTable, TableHeader as UiTableHeader, TableBody as UiTableBody, TableRow as UiTableRow, TableHead as UiTableHead, TableCell as UiTableCell } from '@/components/ui/table';
TXT;
        }

        $out = [];
        foreach ($imports as $name => $path) {
            $safe = str_replace(' ', '', $name);
            $out[] = "import { {$safe} as Ui{$safe} } from '{$path}';";
        }

        return implode("\n", $out);
    }

    protected function buildVueHeaders(CrudDefinition $definition): string
    {
        $lines = [];
        foreach ($definition->fields as $field) {
            $name = $field['name'] ?? null;
            if (! is_string($name) || $name === '' || $name === 'id') {
                continue;
            }
            $lines[] = '                        <UiTableHead>'.Str::title(str_replace('_', ' ', $name)).'</UiTableHead>';
        }

        return implode("\n", $lines);
    }

    protected function buildVueCells(CrudDefinition $definition): string
    {
        $lines = [];
        foreach ($definition->fields as $field) {
            $name = $field['name'] ?? null;
            if (! is_string($name) || $name === '' || $name === 'id') {
                continue;
            }
            $lines[] = "                            <UiTableCell>{{ item.{$name} }}</UiTableCell>";
        }

        return implode("\n", $lines);
    }

    protected function buildVueInitialValues(CrudDefinition $definition): string
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
            $lines[] = "    {$name}: {$default},";
        }

        return implode("\n", $lines);
    }

    protected function buildVueShowRows(CrudDefinition $definition): string
    {
        $lines = [];
        foreach ($definition->fields as $field) {
            $name = $field['name'] ?? null;
            if (! is_string($name) || $name === '' || $name === 'id') {
                continue;
            }
            $label = Str::title(str_replace('_', ' ', $name));
            $lines[] = <<<TPL
                <div class="flex justify-between p-3">
                    <span class="font-medium">{$label}</span>
                    <span>{{ {{ modelVariable }}?.{$name} }}</span>
                </div>
TPL;
        }

        return implode("\n", $lines);
    }
}
