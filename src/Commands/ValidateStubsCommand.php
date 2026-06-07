<?php

declare(strict_types=1);

namespace SajidUlIslam\CrudGenerator\Commands;

use Illuminate\Console\Command;
use SajidUlIslam\CrudGenerator\Enums\Stack;
use SajidUlIslam\CrudGenerator\GeneratorFactory;

class ValidateStubsCommand extends Command
{
    protected $signature = 'crud:validate-stubs
                            {--stack= : Stack to validate (default: all)}
                            {--path= : Path to stubs directory (default: package built-ins)}';

    protected $description = 'Validate that stubs exist and contain all required variables.';

    /**
     * Variables that must appear in every stub.
     *
     * @var array<string, array<int, string>>
     */
    protected array $requiredVariables = [
        'controller.stub' => ['modelName', 'modelVariable', 'modelPluralVariable', 'routePrefix'],
        'Index.tsx.stub' => ['modelName', 'modelPluralVariable', 'tsInterface', 'reactImports'],
        'Index.vue.stub' => ['modelName', 'modelPluralVariable', 'vueImports'],
        'Index.svelte.stub' => ['modelName', 'modelPluralVariable', 'svelteImports'],
        'Index.php.stub' => ['modelName', 'modelPluralVariable'],
        'index.blade.stub' => ['modelName', 'modelPluralVariable', 'routePrefix'],
        'Create.tsx.stub' => ['modelName', 'routePrefix', 'reactImports'],
        'Edit.tsx.stub' => ['modelName', 'routePrefix', 'reactImports'],
        'Show.tsx.stub' => ['modelName', 'routePrefix'],
        'model.stub' => ['modelName', 'fillableFields'],
        'migration.stub' => ['tableName', 'migrationFields'],
        'service.stub' => ['modelName', 'serviceNamespace'],
        'repository.stub' => ['modelName'],
        'repository-interface.stub' => ['modelName', 'repositoryContractNamespace'],
    ];

    public function handle(): int
    {
        $stack = (string) ($this->option('stack') ?? '');
        $path = (string) ($this->option('path') ?? '');

        $stacks = $stack !== '' ? [$stack] : Stack::values();
        $hasFailure = false;

        foreach ($stacks as $key) {
            if (! in_array($key, Stack::values(), true)) {
                $this->error("Unknown stack: {$key}");
                $hasFailure = true;

                continue;
            }

            $stubRoot = $path !== '' ? rtrim($path, '/').'/'.$key : GeneratorFactory::make($key)->getStubPath();
            $this->info("Validating stubs for [{$key}] at {$stubRoot}");

            if (! is_dir($stubRoot)) {
                $this->error("  Stub directory missing: {$stubRoot}");
                $hasFailure = true;

                continue;
            }

            $files = glob($stubRoot.'/*') ?: [];
            foreach ($files as $file) {
                if (is_dir($file)) {
                    continue;
                }
                $name = basename($file);
                $content = file_get_contents($file);
                $required = $this->requiredVariables[$name] ?? ['modelName', 'modelVariable'];
                $missing = array_values(array_filter($required, fn ($v) => ! str_contains($content, '{{ '.$v.' }}')));

                if ($missing === []) {
                    $this->line("  ✔ {$name}");
                } else {
                    $this->line('  ✗ '.$name.' (missing: '.implode(', ', $missing).')');
                    $hasFailure = true;
                }
            }
        }

        return $hasFailure ? self::FAILURE : self::SUCCESS;
    }
}
