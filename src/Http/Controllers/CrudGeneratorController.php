<?php

namespace SajidUlIslam\CrudGenerator\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SajidUlIslam\CrudGenerator\Services\CrudGeneratorService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CrudGeneratorController extends Controller
{
    protected $crudService;

    public function __construct(CrudGeneratorService $crudService)
    {
        $this->crudService = $crudService;
    }

    public function index()
    {
        $tables = $this->getTables();
        $fieldTypes = config('crud-generator.field_types');
        
        return view('crud-generator::index', compact('tables', 'fieldTypes'));
    }

    public function generate(Request $request)
    {
        $request->validate([
            'table_name' => 'required|string',
            'model_name' => 'required|string',
            'fields' => 'required|array',
            'fields.*.name' => 'required|string',
            'fields.*.type' => 'required|string',
            'fields.*.validation' => 'nullable|string',
        ]);

        try {
            $result = $this->crudService->generateCrud([
                'table_name' => $request->table_name,
                'model_name' => $request->model_name,
                'fields' => $request->fields,
                'with_migration' => $request->boolean('with_migration', true),
                'with_seeder' => $request->boolean('with_seeder', false),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'CRUD generated successfully!',
                'files' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating CRUD: ' . $e->getMessage()
            ], 500);
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
                'suggested_model' => $this->tableToModelName($table)
            ];
        }

        return response()->json($models);
    }

    private function getTables()
    {
        try {
            // For Laravel 11+, use the new Schema facade method
            if (method_exists(Schema::class, 'getTables')) {
                return collect(Schema::getTables())->pluck('name')->toArray();
            }
            
            // For Laravel 10 and below, try the Doctrine method first
            if (method_exists(DB::connection(), 'getDoctrineSchemaManager')) {
                return DB::connection()->getDoctrineSchemaManager()->listTableNames();
            }
            
            // Fallback: Use raw SQL queries based on database type
            return $this->getTablesUsingRawSql();
            
        } catch (\Exception $e) {
            // Final fallback to raw SQL
            return $this->getTablesUsingRawSql();
        }
    }

    private function getTablesUsingRawSql()
    {
        $connection = DB::connection();
        $database = $connection->getDatabaseName();
        $driver = $connection->getDriverName();

        try {
            switch ($driver) {
                case 'mysql':
                    return DB::select("SELECT table_name as `name` FROM information_schema.tables WHERE table_schema = ?", [$database]);
                    
                case 'pgsql':
                    return DB::select("SELECT tablename as name FROM pg_catalog.pg_tables WHERE schemaname = 'public'");
                    
                case 'sqlite':
                    return collect(DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"))
                        ->pluck('name')->toArray();
                    
                case 'sqlsrv':
                    return DB::select("SELECT table_name as name FROM information_schema.tables WHERE table_type = 'BASE TABLE'");
                    
                default:
                    // Generic fallback - might not work for all databases
                    return DB::select("SHOW TABLES");
            }
        } catch (\Exception $e) {
            // Return empty array if all methods fail
            return [];
        }
    }

    private function tableToModelName($tableName)
    {
        return \Illuminate\Support\Str::studly(\Illuminate\Support\Str::singular($tableName));
    }
}