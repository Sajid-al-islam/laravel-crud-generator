<?php

namespace SajidUlIslam\CrudGenerator\Commands;

use Illuminate\Console\Command;
use SajidUlIslam\CrudGenerator\Services\CrudGeneratorService;

class CrudGenerateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crud:generate 
                            {model : The name of the model}
                            {--table= : The name of the table}
                            {--fields= : Fields in JSON format}
                            {--no-migration : Skip migration generation}
                            {--with-seeder : Generate seeder}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate CRUD operations for a model';

    protected $crudService;

    public function __construct(CrudGeneratorService $crudService)
    {
        parent::__construct();
        $this->crudService = $crudService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $modelName = $this->argument('model');
        $tableName = $this->option('table') ?: strtolower($modelName) . 's';
        
        // Get fields from option or prompt user
        $fields = $this->getFields();
        
        if (empty($fields)) {
            $this->error('No fields provided. Use --fields option or run the web interface.');
            return 1;
        }

        try {
            $this->info("Generating CRUD for {$modelName}...");
            
            $result = $this->crudService->generateCrud([
                'model_name' => $modelName,
                'table_name' => $tableName,
                'fields' => $fields,
                'with_migration' => !$this->option('no-migration'),
                'with_seeder' => $this->option('with-seeder'),
            ]);

            $this->info('CRUD generated successfully!');
            $this->line('Generated files:');
            
            foreach ($result as $type => $file) {
                if (is_array($file)) {
                    foreach ($file as $f) {
                        $this->line("  - {$f} ({$type})");
                    }
                } else {
                    $this->line("  - {$file} ({$type})");
                }
            }
            
            $this->warn('Don\'t forget to run: php artisan migrate');
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Error generating CRUD: ' . $e->getMessage());
            return 1;
        }
    }

    protected function getFields()
    {
        $fieldsOption = $this->option('fields');
        
        if ($fieldsOption) {
            return json_decode($fieldsOption, true);
        }

        // Interactive mode
        $fields = [];
        $this->info('Enter fields for your model (leave name empty to finish):');
        
        while (true) {
            $name = $this->ask('Field name eg: ttile');
            if (empty($name)) {
                break;
            }
            
            $type = $this->choice('Field type', [
                'string', 'text', 'integer', 'boolean', 'date', 'datetime', 'email', 'password'
            ], 'string');
            
            $validation = $this->ask('Validation rules (optional) eg: required|nullable', '');
            $nullable = $this->confirm('Nullable?', false);
            
            $fields[] = [
                'name' => $name,
                'type' => $type,
                'validation' => $validation,
                'nullable' => $nullable,
            ];
        }
        
        return $fields;
    }
}