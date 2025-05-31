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
        return DB::connection()->getDoctrineSchemaManager()->listTableNames();
    }

    private function tableToModelName($tableName)
    {
        return \Illuminate\Support\Str::studly(\Illuminate\Support\Str::singular($tableName));
    }
}