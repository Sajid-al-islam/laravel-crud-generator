<?php

declare(strict_types=1);

namespace SajidUlIslam\CrudGenerator\Commands;

use Illuminate\Console\Command;
use SajidUlIslam\CrudGenerator\CrudDefinition;
use SajidUlIslam\CrudGenerator\Enums\Stack;
use SajidUlIslam\CrudGenerator\GeneratorFactory;
use SajidUlIslam\CrudGenerator\Services\ConfigLoader;
use SajidUlIslam\CrudGenerator\Services\FieldParser;
use SajidUlIslam\CrudGenerator\Services\StubManager;

class MakeCrudCommand extends Command
{
    protected $signature = 'make:crud
                            {table? : The name of the model/table}
                            {--stack= : blade|api|react|vue|svelte|livewire|nova|filament}
                            {--service : Generate service class}
                            {--repository : Generate repository (implies --service)}
                            {--no-migration : Skip migration}
                            {--no-model : Skip model}
                            {--no-requests : Skip form requests}
                            {--no-routes : Skip route registration}
                            {--custom-stubs= : Path to custom stubs directory}
                            {--force : Overwrite existing files}
                            {--dry-run : Show what would be generated without writing files}
                            {--fields= : JSON array of field definitions}';

    protected $description = 'Generate complete CRUD operations for any Laravel stack.';

    public function handle(StubManager $stubs, ConfigLoader $config, FieldParser $parser): int
    {
        $allowedEnvironments = config('crud-generator.allowed_environments', ['local']);
        if (! app()->environment($allowedEnvironments)) {
            $this->error('CRUD Generator is not available in the "'.app()->environment().'" environment.');

            return self::FAILURE;
        }

        $table = $this->argument('table');
        $stack = (string) ($this->option('stack') ?? $config->get('stack') ?? config('crud-generator.default_stack', 'blade'));

        if (! $table) {
            [$table, $stack, $withService, $fields] = $this->interactiveWizard($stack);
        } else {
            $withService = (bool) $this->option('service');
            $fields = $this->parseFieldsOption($parser) ?? $this->promptFieldsInteractive();
        }

        if ($this->option('repository')) {
            $withService = true;
        }

        $modelName = str($table)->studly()->singular()->toString();
        $tableName = str($table)->snake()->lower()->toString();

        if (! in_array($stack, Stack::values(), true)) {
            $this->error("Unsupported stack [{$stack}]. Supported: ".implode(', ', Stack::values()));

            return self::FAILURE;
        }

        $options = [
            'force' => (bool) $this->option('force'),
            'with_migration' => ! $this->option('no-migration'),
            'with_model' => ! $this->option('no-model'),
            'with_requests' => ! $this->option('no-requests'),
            'register_routes' => ! $this->option('no-routes'),
            'layout' => config('crud-generator.default_layout', 'layouts.app'),
        ];

        $definition = new CrudDefinition(
            modelName: $modelName,
            tableName: $tableName,
            fields: $fields,
            stack: $stack,
            withService: $withService,
            withRepository: (bool) $this->option('repository'),
            customStubPath: $this->option('custom-stubs'),
            options: $options,
        );

        if ($this->option('dry-run')) {
            return $this->dryRun($definition);
        }

        $start = microtime(true);

        try {
            $driver = GeneratorFactory::make($stack, $definition);
            $result = $driver->generate($definition);
        } catch (\Throwable $e) {
            $this->error('Generation failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $duration = round(microtime(true) - $start, 2);

        $this->renderResult($result);

        $created = collect($result)->flatten(2)->filter(fn ($r) => is_array($r) && ($r['status'] ?? null) === 'created')->count();
        $this->line('');
        $this->info("Generated {$created} files in {$duration}s");

        if ($definition->options['with_migration'] ?? true) {
            $this->warn("Don't forget to run: php artisan migrate");
        }

        return self::SUCCESS;
    }

    /**
     * @return array{0:string,1:string,2:bool,3:array<int, array<string, mixed>>}
     */
    protected function interactiveWizard(string $defaultStack): array
    {
        $promptsAvailable = class_exists(\Laravel\Prompts\Prompt::class);

        if ($promptsAvailable) {
            return $this->promptsWizard($defaultStack);
        }

        return $this->legacyWizard($defaultStack);
    }

    /**
     * @return array{0:string,1:string,2:bool,3:array<int, array<string, mixed>>}
     */
    protected function promptsWizard(string $defaultStack): array
    {
        $table = \Laravel\Prompts\text('Which table?', placeholder: 'posts', required: true);
        $stack = \Laravel\Prompts\select('Which stack?', $this->stackOptions(), $defaultStack);
        $withService = \Laravel\Prompts\confirm('Generate service class?', $this->shouldSuggestService());
        $fields = $this->collectFieldsWithPrompts();

        return [$table, $stack, $withService, $fields];
    }

    /**
     * @return array{0:string,1:string,2:bool,3:array<int, array<string, mixed>>}
     */
    protected function legacyWizard(string $defaultStack): array
    {
        $table = $this->ask('Which table?', 'posts');
        $stack = $this->choice('Which stack?', Stack::values(), $defaultStack);
        $withService = $this->confirm('Generate service class?', $this->shouldSuggestService());
        $fields = $this->collectFieldsLegacy();

        return [$table, $stack, $withService, $fields];
    }

    /**
     * @return array<string, string>
     */
    protected function stackOptions(): array
    {
        $out = [];
        foreach (Stack::cases() as $stack) {
            $out[$stack->value] = $stack->label();
        }

        return $out;
    }

    protected function shouldSuggestService(): bool
    {
        return (string) config('crud-generator.service_layer.pattern', 'suggested') === 'suggested';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function collectFieldsWithPrompts(): array
    {
        $fields = [];
        $this->info('Add fields (leave name empty to finish):');

        while (true) {
            $name = \Laravel\Prompts\text('Field name', placeholder: 'title');
            if ($name === '') {
                break;
            }
            $type = \Laravel\Prompts\select('Type', $this->fieldTypeOptions(), 'string');
            $validation = \Laravel\Prompts\text('Validation rules', placeholder: 'required|max:255', default: 'nullable');
            $nullable = \Laravel\Prompts\confirm('Nullable?', false);

            $fields[] = [
                'name' => $name,
                'type' => $type,
                'validation' => $validation,
                'nullable' => $nullable,
                'searchable' => in_array($type, ['string', 'text'], true),
                'sortable' => ! in_array($type, ['text', 'longText', 'json'], true),
            ];
        }

        return $fields;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function collectFieldsLegacy(): array
    {
        $fields = [];
        $this->info('Add fields (leave name empty to finish):');

        while (true) {
            $name = $this->ask('Field name (empty to finish)');
            if (empty($name)) {
                break;
            }
            $type = $this->choice('Type', array_keys($this->fieldTypeOptions()), 'string');
            $validation = $this->ask('Validation rules', 'nullable');
            $nullable = $this->confirm('Nullable?', false);

            $fields[] = [
                'name' => $name,
                'type' => $type,
                'validation' => $validation,
                'nullable' => $nullable,
                'searchable' => in_array($type, ['string', 'text'], true),
                'sortable' => ! in_array($type, ['text', 'longText', 'json'], true),
            ];
        }

        return $fields;
    }

    /**
     * @return array<string, string>
     */
    protected function fieldTypeOptions(): array
    {
        return [
            'string' => 'String',
            'text' => 'Text',
            'longText' => 'Long Text',
            'integer' => 'Integer',
            'bigInteger' => 'Big Integer',
            'boolean' => 'Boolean',
            'date' => 'Date',
            'datetime' => 'DateTime',
            'decimal' => 'Decimal',
            'float' => 'Float',
            'json' => 'JSON',
            'email' => 'Email',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    protected function parseFieldsOption(FieldParser $parser): ?array
    {
        $raw = $this->option('fields');
        if (! is_string($raw) || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            $this->error('Invalid JSON in --fields option.');

            return null;
        }

        return $decoded;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function promptFieldsInteractive(): array
    {
        if (class_exists(\Laravel\Prompts\Prompt::class)) {
            return $this->collectFieldsWithPrompts();
        }

        return $this->collectFieldsLegacy();
    }

    protected function dryRun(CrudDefinition $definition): int
    {
        $this->info('Dry run — no files will be written.');
        $this->line("Model:   {$definition->modelName}");
        $this->line("Table:   {$definition->tableName}");
        $this->line("Stack:   {$definition->stack}");
        $this->line('Service: '.($definition->withService ? 'yes' : 'no'));
        $this->line('Repository: '.($definition->withRepository ? 'yes' : 'no'));
        $this->line('Fields:  '.count($definition->fields));

        $this->line('');
        $this->line('Driver: '.get_class(GeneratorFactory::make($definition->stack, $definition)));
        $this->line('Stub path: '.GeneratorFactory::make($definition->stack, $definition)->getStubPath());

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    protected function renderResult(array $result): void
    {
        foreach ($result as $role => $value) {
            if ($role === 'routes') {
                $path = is_array($value) ? ($value['path'] ?? null) : $value;
                $status = is_array($value) ? ($value['status'] ?? 'modified') : 'modified';
                if ($path) {
                    $this->line($this->statusGlyph($status)." {$path} ({$role})");
                }
                continue;
            }

            if (is_array($value) && isset($value[0]) && is_array($value[0])) {
                foreach ($value as $file) {
                    $this->line($this->statusGlyph($file['status'] ?? 'created').' '.$file['path']);
                }
                continue;
            }

            if (is_array($value) && isset($value['path'])) {
                $this->line($this->statusGlyph($value['status'] ?? 'created').' '.$value['path']);
                continue;
            }

            if (is_string($value)) {
                $this->line($this->statusGlyph('created').' '.$value);
            }
        }
    }

    protected function statusGlyph(string $status): string
    {
        return match ($status) {
            'created' => '✔',
            'modified' => '~',
            'overwritten' => '~',
            'skipped' => '⚠',
            default => '•',
        };
    }
}
