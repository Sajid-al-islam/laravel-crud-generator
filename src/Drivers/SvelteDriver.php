<?php

declare(strict_types=1);

namespace SajidUlIslam\CrudGenerator\Drivers;

use Illuminate\Support\Str;
use SajidUlIslam\CrudGenerator\CrudDefinition;
use SajidUlIslam\CrudGenerator\Enums\Stack;

class SvelteDriver extends InertiaDriver
{
    public function getStackName(): string
    {
        return 'Svelte (Inertia + shadcn-svelte)';
    }

    public function getStackKey(): Stack
    {
        return Stack::Svelte;
    }

    protected function resolveStackKey(): Stack
    {
        return Stack::Svelte;
    }

    protected function generateFrontend(CrudDefinition $definition, bool $force): array
    {
        $writes = [];
        $pagesPath = resource_path('js/pages/'.$definition->modelNamePlural());

        $ts = $this->tsVars($definition);
        $vars = array_merge($definition->variables(), $ts, [
            'svelteImports' => $this->imports(),
            'tableHeaders' => $this->buildSvelteHeaders($definition),
            'tableCells' => $this->buildSvelteCells($definition),
            'formFields' => $this->fields->svelteFormFields($definition->fields),
            'formInitialValues' => $this->buildSvelteInitialValues($definition),
            'showRows' => $this->buildSvelteShowRows($definition),
        ]);

        foreach (['Index', 'Create', 'Edit', 'Show'] as $page) {
            $writes[strtolower($page)] = $this->makeFile(
                $definition,
                $page.'.svelte.stub',
                $pagesPath.'/'.$page.'.svelte',
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
            return '';
        }

        $out = [];
        foreach ($imports as $name => $path) {
            $out[] = "import { {$name} } from '{$path}';";
        }

        return implode("\n", $out);
    }

    protected function buildSvelteHeaders(CrudDefinition $definition): string
    {
        $lines = [];
        foreach ($definition->fields as $field) {
            $name = $field['name'] ?? null;
            if (! is_string($name) || $name === '' || $name === 'id') {
                continue;
            }
            $lines[] = '                        <th class="p-2 text-left">'.Str::title(str_replace('_', ' ', $name)).'</th>';
        }

        return implode("\n", $lines);
    }

    protected function buildSvelteCells(CrudDefinition $definition): string
    {
        $lines = [];
        foreach ($definition->fields as $field) {
            $name = $field['name'] ?? null;
            if (! is_string($name) || $name === '' || $name === 'id') {
                continue;
            }
            $lines[] = "                            <td class=\"p-2\">{item.{$name}}</td>";
        }

        return implode("\n", $lines);
    }

    protected function buildSvelteInitialValues(CrudDefinition $definition): string
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

    protected function buildSvelteShowRows(CrudDefinition $definition): string
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
                    <span>{ {{ modelVariable }}.{$name} }</span>
                </div>
TPL;
        }

        return implode("\n", $lines);
    }
}
