<?php

declare(strict_types=1);

namespace SajidUlIslam\CrudGenerator\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use SajidUlIslam\CrudGenerator\CrudDefinition;
use SajidUlIslam\CrudGenerator\GeneratorFactory;
use SajidUlIslam\CrudGenerator\Services\FieldParser;

class CrudGeneratorController extends Controller
{
    public function __construct(
        protected FieldParser $fields,
    ) {}

    public function index()
    {
        $tables = $this->getTables();
        $fieldTypes = config('crud-generator.field_types');
        $stacks = config('crud-generator.stacks');

        return view('crud-generator::index', compact('tables', 'fieldTypes', 'stacks'));
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'table_name' => 'required|string',
            'model_name' => 'required|string',
            'fields' => 'required|array',
            'fields.*.name' => 'required|string',
            'fields.*.type' => 'required|string',
            'fields.*.validation' => 'nullable|string',
            'stack' => 'nullable|string',
            'layout' => 'nullable|string',
            'with_migration' => 'nullable|boolean',
            'with_seeder' => 'nullable|boolean',
            'api_mode' => 'nullable|boolean',
            'with_service' => 'nullable|boolean',
            'with_repository' => 'nullable|boolean',
            'custom_stubs' => 'nullable|string',
            'force' => 'nullable|boolean',
        ]);

        $stack = $validated['stack'] ?? config('crud-generator.default_stack', 'blade');
        if (! empty($validated['api_mode'])) {
            $stack = 'api';
        }

        $definition = new CrudDefinition(
            modelName: $validated['model_name'],
            tableName: $validated['table_name'],
            fields: $validated['fields'],
            stack: $stack,
            withService: (bool) ($validated['with_service'] ?? false),
            withRepository: (bool) ($validated['with_repository'] ?? false),
            customStubPath: $validated['custom_stubs'] ?? null,
            options: [
                'with_migration' => (bool) ($validated['with_migration'] ?? true),
                'register_routes' => true,
                'force' => (bool) ($validated['force'] ?? false),
                'layout' => $validated['layout'] ?? config('crud-generator.default_layout', 'layouts.app'),
            ],
        );

        try {
            $driver = GeneratorFactory::make($stack, $definition);
            $result = $driver->generate($definition);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating CRUD: '.$e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'CRUD generated successfully!',
            'files' => $result,
        ]);
    }

    public function preview(Request $request)
    {
        $validated = $request->validate([
            'table_name' => 'required|string',
            'model_name' => 'required|string',
            'fields' => 'required|array',
            'stack' => 'nullable|string',
        ]);

        $stack = $validated['stack'] ?? config('crud-generator.default_stack', 'blade');
        $definition = new CrudDefinition(
            modelName: $validated['model_name'],
            tableName: $validated['table_name'],
            fields: $validated['fields'],
            stack: $stack,
        );

        try {
            $driver = GeneratorFactory::make($stack, $definition);

            $preview = [
                'stack' => $stack,
                'driver' => $driver::class,
                'stub_path' => $driver->getStubPath(),
                'stack_label' => $definition->stackEnum()->label(),
                'model_name' => $definition->modelName,
                'table_name' => $definition->tableName,
                'route_prefix' => $definition->routePrefix(),
                'files' => [],
            ];

            return response()->json(['success' => true, 'preview' => $preview]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function getModels()
    {
        $tables = $this->getTables();
        $models = [];

        foreach ($tables as $table) {
            $columns = Schema::getColumnListing($table);
            $models[$table] = [
                'table' => $table,
                'columns' => $columns,
                'suggested_model' => $this->tableToModelName($table),
            ];
        }

        return response()->json($models);
    }

    /**
     * @return array<int, string>
     */
    private function getTables(): array
    {
        try {
            if (method_exists(Schema::class, 'getTables')) {
                return collect(Schema::getTables())->pluck('name')->toArray();
            }
            if (method_exists(DB::connection(), 'getDoctrineSchemaManager')) {
                return DB::connection()->getDoctrineSchemaManager()->listTableNames();
            }

            return $this->getTablesUsingRawSql();
        } catch (\Exception $e) {
            return $this->getTablesUsingRawSql();
        }
    }

    /**
     * @return array<int, string>
     */
    private function getTablesUsingRawSql(): array
    {
        $connection = DB::connection();
        $database = $connection->getDatabaseName();
        $driver = $connection->getDriverName();

        try {
            return match ($driver) {
                'mysql' => collect(DB::select("SELECT table_name as `name` FROM information_schema.tables WHERE table_schema = ?", [$database]))->pluck('name')->toArray(),
                'pgsql' => collect(DB::select("SELECT tablename as name FROM pg_catalog.pg_tables WHERE schemaname = 'public'"))->pluck('name')->toArray(),
                'sqlite' => collect(DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"))->pluck('name')->toArray(),
                'sqlsrv' => collect(DB::select("SELECT table_name as name FROM information_schema.tables WHERE table_type = 'BASE TABLE'"))->pluck('name')->toArray(),
                default => [],
            };
        } catch (\Exception $e) {
            return [];
        }
    }

    private function tableToModelName(string $tableName): string
    {
        return \Illuminate\Support\Str::studly(\Illuminate\Support\Str::singular($tableName));
    }
}
