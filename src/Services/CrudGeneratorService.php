<?php

namespace SajidUlIslam\CrudGenerator\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CrudGeneratorService
{
    protected $stubPath;

    public function __construct()
    {
        $this->stubPath = __DIR__ . '/../stubs/';
    }

    public function generateCrud($data)
    {
        $modelName = $data['model_name'];
        $tableName = $data['table_name'];
        $fields = $data['fields'];

        $generatedFiles = [];

        // Generate Model
        $generatedFiles['model'] = $this->generateModel($modelName, $tableName, $fields);

        // Generate Migration (if requested)
        if ($data['with_migration']) {
            $generatedFiles['migration'] = $this->generateMigration($tableName, $fields);
        }

        // Generate Controller
        $generatedFiles['controller'] = $this->generateController($modelName, $fields);

        // Generate Request
        $generatedFiles['request'] = $this->generateRequest($modelName, $fields);

        // Generate Views
        $generatedFiles['views'] = $this->generateViews($modelName, $fields);

        // Add Routes
        $generatedFiles['routes'] = $this->addRoutes($modelName);

        return $generatedFiles;
    }

    protected function generateModel($modelName, $tableName, $fields)
    {
        $stub = File::get($this->stubPath . 'model.stub');

        $fillable = collect($fields)->pluck('name')->map(function ($field) {
            return "'$field'";
        })->implode(', ');

        $replacements = [
            '{{ModelName}}' => $modelName,
            '{{tableName}}' => $tableName,
            '{{fillable}}' => $fillable,
        ];

        $content = str_replace(array_keys($replacements), array_values($replacements), $stub);

        $path = app_path("Models/{$modelName}.php");
        File::put($path, $content);

        return $path;
    }

    protected function generateMigration($tableName, $fields)
    {
        $stub = File::get($this->stubPath . 'migration.stub');

        $fieldsContent = '';
        foreach ($fields as $field) {
            $fieldsContent .= $this->getMigrationFieldLine($field) . "\n";
        }

        $className = 'Create' . Str::studly($tableName) . 'Table';

        $replacements = [
            '{{ClassName}}' => $className,
            '{{tableName}}' => $tableName,
            '{{fields}}' => $fieldsContent,
        ];

        $content = str_replace(array_keys($replacements), array_values($replacements), $stub);

        $timestamp = date('Y_m_d_His');
        $filename = "{$timestamp}_create_{$tableName}_table.php";
        $path = database_path("migrations/{$filename}");

        File::put($path, $content);

        return $path;
    }

    protected function generateController($modelName, $fields)
    {
        $stub = File::get($this->stubPath . 'controller.stub');

        $modelVariable = Str::camel($modelName);
        $modelPluralVariable = Str::camel(Str::plural($modelName));

        $replacements = [
            '{{ModelName}}' => $modelName,
            '{{modelVariable}}' => $modelVariable,
            '{{modelPluralVariable}}' => $modelPluralVariable,
            '{{requestName}}' => $modelName . 'Request',
        ];

        $content = str_replace(array_keys($replacements), array_values($replacements), $stub);

        $path = app_path("Http/Controllers/{$modelName}Controller.php");
        File::put($path, $content);

        return $path;
    }

    protected function generateRequest($modelName, $fields)
    {
        try {
            $stub = File::get($this->stubPath . 'request.stub');

            $rules = '';
            foreach ($fields as $field) {
                if (!empty($field['validation'])) {
                    $rules .= "            '{$field['name']}' => '{$field['validation']}',\n";
                }
            }

            $replacements = [
                '{{RequestName}}' => $modelName . 'Request',
                '{{rules}}' => $rules,
            ];

            $content = str_replace(array_keys($replacements), array_values($replacements), $stub);

            $requestPath = app_path('Http' . DIRECTORY_SEPARATOR . 'Requests');

            if (!File::exists($requestPath)) {
                File::makeDirectory($requestPath, 0755, true);
            }

            if (!is_writable($requestPath)) {
                chmod($requestPath, 0755);
            }

            $filePath = $requestPath . DIRECTORY_SEPARATOR . $modelName . 'Request.php';


            if (File::exists($filePath)) {
                // Make file writable if it exists
                chmod($filePath, 0644);
            }

            $result = File::put($filePath, $content);

            if ($result === false) {
                throw new \Exception("Failed to write file: {$filePath}");
            }

            chmod($filePath, 0644);

            return $filePath;
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Error generating request file: ' . $e->getMessage(), [
                'model_name' => $modelName,
                'path' => $filePath ?? 'unknown',
                'directory_exists' => File::exists($requestPath ?? ''),
                'directory_writable' => is_writable($requestPath ?? ''),
            ]);

            throw new \Exception("Could not generate request file: " . $e->getMessage());
        }
    }

    protected function generateViews($modelName, $fields)
    {
        $viewPath = resource_path('views/' . Str::lower(Str::plural($modelName)));

        if (!File::exists($viewPath)) {
            File::makeDirectory($viewPath, 0755, true);
        }

        $views = ['index', 'create', 'edit', 'show'];
        $generatedViews = [];

        foreach ($views as $view) {
            $stub = File::get($this->stubPath . "views/{$view}.stub");
            $content = $this->replaceViewPlaceholders($stub, $modelName, $fields);

            $filePath = $viewPath . "/{$view}.blade.php";
            File::put($filePath, $content);
            $generatedViews[] = $filePath;
        }

        return $generatedViews;
    }

    protected function addRoutes($modelName)
    {
        // 1) Determine the “slug” for routes and controller class
        $routeName      = Str::lower(Str::plural($modelName));     // e.g. “posts”
        $controllerName = $modelName . 'Controller';               // e.g. “PostController”

        // 2) Fully-qualified controller class:
        $fqcn = "App\\Http\\Controllers\\{$controllerName}";

        // Build a “use” statement
        $useStatement = "use {$fqcn};";

        // 3) Build the exact route line we want to append:
        //    e.g. Route::resource('posts', PostController::class);
        $routeLine = "Route::resource('{$routeName}', {$controllerName}::class);";

        // 4) Read the existing routes/web.php
        $routesPath    = base_path('routes/web.php');
        $routesContent = File::get($routesPath);

        // 5) If there is no “use App\Http\Controllers\PostController;” yet, insert it.
        if (strpos($routesContent, $useStatement) === false) {
            // We prefer to insert after the “<?php” and any existing “use …” lines.
            // Strategy: Find the closing line of the initial “use Illuminate\Support\Facades\Route;”
            // (or the last “use …;”), and insert our use-statement right after that block.

            // A simple (but reliable) way:
            //   – Look for the first occurrence of `use Illuminate\Support\Facades\Route;`
            //   – Then insert our new line immediately after it (keeping the blank line if present).
            //
            // If your web.php has multiple “use …” statements, this will place ours right after Route's import.
            //
            // Fallback: If for some reason “use Illuminate\Support\Facades\Route;” is missing,
            // we’ll just insert immediately after “<?php”.

            if (preg_match('/\A(<\?php\s*)(.*?)use\s+Illuminate\\\\Support\\\\Facades\\\\Route;/s', $routesContent, $matches)) {
                // $matches[0] is everything from “<?php” up through the Route import
                // $matches[1] = “<?php” + any whitespace up to first “use …”
                // $matches[2] is everything up through “use Illuminate\Support\Facades\Route;”
                $insertPosition = strpos($routesContent, $matches[0]) + strlen($matches[0]);
                $insertion     = "\n{$useStatement}\n";

                $routesContent = substr_replace($routesContent, $insertion, $insertPosition, 0);
            } else {
                // Fallback: insert right after “<?php” tag:
                if (preg_match('/\A(<\?php\s*)/s', $routesContent, $m)) {
                    $insertPosition = strlen($m[1]);
                    $insertion     = "\n{$useStatement}\n";
                    $routesContent = substr_replace($routesContent, $insertion, $insertPosition, 0);
                } else {
                    // If somehow “<?php” isn’t at the top (very unlikely), just prepend our use:
                    $routesContent = "<?php\n{$useStatement}\n" . ltrim($routesContent);
                }
            }

            // Overwrite the file with our new “use …” included
            File::put($routesPath, $routesContent);
        }

        // 6) Re-read the file, in case we modified it above
        $routesContent = File::get($routesPath);

        // 7) Check if the route line already exists. If not, append it.
        if (strpos($routesContent, $routeLine) === false) {
            // We’ll just append at the end (with a leading newline for readability):
            File::append($routesPath, "\n" . $routeLine . "\n");
        }

        return $routeLine;
    }

    protected function getMigrationFieldLine($field)
    {
        $line = "            \$table->";

        switch ($field['type']) {
            case 'string':
                $line .= "string('{$field['name']}')";
                break;
            case 'text':
                $line .= "text('{$field['name']}')";
                break;
            case 'integer':
                $line .= "integer('{$field['name']}')";
                break;
            case 'boolean':
                $line .= "boolean('{$field['name']}')";
                break;
            case 'date':
                $line .= "date('{$field['name']}')";
                break;
            case 'datetime':
                $line .= "dateTime('{$field['name']}')";
                break;
            case 'email':
                $line .= "string('{$field['name']}')";
                break;
            default:
                $line .= "string('{$field['name']}')";
        }

        if (isset($field['nullable']) && $field['nullable']) {
            $line .= "->nullable()";
        }

        $line .= ";";

        return $line;
    }

    protected function replaceViewPlaceholders($stub, $modelName, $fields)
    {
        $modelVariable = Str::camel($modelName);
        $modelPluralVariable = Str::camel(Str::plural($modelName));
        $routePrefix = Str::lower(Str::plural($modelName));

        // Generate different form fields for create and edit
        $createFormFields = $this->generateFormFields($fields, 'create', $modelVariable);
        $editFormFields = $this->generateFormFields($fields, 'edit', $modelVariable);

        $tableHeaders = $this->generateTableHeaders($fields);
        $tableData = $this->generateTableData($fields, $modelVariable);
        $showFields = $this->generateShowFields($fields, $modelVariable);

        $replacements = [
            '{{ModelName}}' => $modelName,
            '{{modelVariable}}' => $modelVariable,
            '{{modelPluralVariable}}' => $modelPluralVariable,
            '{{routePrefix}}' => $routePrefix,
            '{{formFields}}' => $createFormFields, // Default for create
            '{{createFormFields}}' => $createFormFields,
            '{{editFormFields}}' => $editFormFields,
            '{{tableHeaders}}' => $tableHeaders,
            '{{tableData}}' => $tableData,
            '{{showFields}}' => $showFields,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }

    protected function generateFormFields($fields, $viewType = 'create', $modelVariable = null)
    {
        $html = '';
        foreach ($fields as $field) {
            $label = Str::title(str_replace('_', ' ', $field['name']));

            $html .= "<div class=\"mb-3\">\n";
            $html .= "    <label for=\"{$field['name']}\" class=\"form-label\">{$label}</label>\n";

            switch ($field['type']) {
                case 'text':
                    if ($viewType === 'edit') {
                        $html .= "    <textarea class=\"form-control\" id=\"{$field['name']}\" name=\"{$field['name']}\">{{ old('{$field['name']}', \${$modelVariable}->{$field['name']} ?? '') }}</textarea>\n";
                    } else {
                        $html .= "    <textarea class=\"form-control\" id=\"{$field['name']}\" name=\"{$field['name']}\">{{ old('{$field['name']}') }}</textarea>\n";
                    }
                    break;

                case 'boolean':
                    $html .= "    <select class=\"form-control\" id=\"{$field['name']}\" name=\"{$field['name']}\">\n";
                    if ($viewType === 'edit') {
                        $html .= "        <option value=\"1\" {{ old('{$field['name']}', \${$modelVariable}->{$field['name']}) == '1' ? 'selected' : '' }}>Yes</option>\n";
                        $html .= "        <option value=\"0\" {{ old('{$field['name']}', \${$modelVariable}->{$field['name']}) == '0' ? 'selected' : '' }}>No</option>\n";
                    } else {
                        $html .= "        <option value=\"1\" {{ old('{$field['name']}') == '1' ? 'selected' : '' }}>Yes</option>\n";
                        $html .= "        <option value=\"0\" {{ old('{$field['name']}') == '0' ? 'selected' : '' }}>No</option>\n";
                    }
                    $html .= "    </select>\n";
                    break;

                case 'password':
                    $html .= "    <input type=\"password\" class=\"form-control\" id=\"{$field['name']}\" name=\"{$field['name']}\">\n";
                    break;

                default:
                    $inputType = $field['type'] === 'email' ? 'email' : ($field['type'] === 'date' ? 'date' : ($field['type'] === 'datetime' ? 'datetime-local' : 'text'));

                    if ($viewType === 'edit') {
                        $html .= "    <input type=\"{$inputType}\" class=\"form-control\" id=\"{$field['name']}\" name=\"{$field['name']}\" value=\"{{ old('{$field['name']}', \${$modelVariable}->{$field['name']} ?? '') }}\">\n";
                    } else {
                        $html .= "    <input type=\"{$inputType}\" class=\"form-control\" id=\"{$field['name']}\" name=\"{$field['name']}\" value=\"{{ old('{$field['name']}') }}\">\n";
                    }
            }
            $html .= "</div>\n\n";
        }

        return $html;
    }

    protected function generateTableHeaders($fields)
    {
        $html = '';
        foreach ($fields as $field) {
            $label = Str::title(str_replace('_', ' ', $field['name']));
            $html .= "            <th>{$label}</th>\n";
        }
        return $html;
    }

    protected function generateTableData($fields, $modelVariable)
    {
        $html = '';
        foreach ($fields as $field) {
            $html .= "                <td>{{ \${$modelVariable}->{$field['name']} }}</td>\n";
        }
        return $html;
    }

    protected function generateShowFields($fields, $modelVariable)
    {
        $html = '';
        foreach ($fields as $field) {
            $label = Str::title(str_replace('_', ' ', $field['name']));
            $html .= "    <div class=\"row mb-2\">\n";
            $html .= "        <div class=\"col-md-3\"><strong>{$label}:</strong></div>\n";
            $html .= "        <div class=\"col-md-9\">{{ \${$modelVariable}->{$field['name']} }}</div>\n";
            $html .= "    </div>\n";
        }
        return $html;
    }
}
