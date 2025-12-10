<?php

namespace SajidUlIslam\CrudGenerator\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CrudGeneratorService
{
    protected $stubPath;

    public function __construct()
    {
        $publishedStubPath = resource_path('stubs/vendor/crud-generator/');
        
        if (File::exists($publishedStubPath)) {
            $this->stubPath = $publishedStubPath;
        } else {
            $this->stubPath = __DIR__ . '/../stubs/';
        }
    }

    /**
     * Get the path to a stub file, checking published location first
     */
    protected function getStubPath($stubName)
    {
        return $this->stubPath . $stubName;
    }

    public function generateCrud($data)
    {
        $modelName = $data['model_name'];
        $tableName = $data['table_name'];
        $fields = $data['fields'];

        $generatedFiles = [];

        $generatedFiles['model'] = $this->generateModel($modelName, $tableName, $fields);

        if ($data['with_migration']) {
            $generatedFiles['migration'] = $this->generateMigration($tableName, $fields);
        }

        $layout = $data['layout'] ?? 'layouts.app';

        $generatedFiles['controller'] = $this->generateController($modelName, $fields, $layout);

        $generatedFiles['request'] = $this->generateRequest($modelName, $fields);

        $generatedFiles['views'] = $this->generateViews($modelName, $fields, $layout);

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

    protected function generateController($modelName, $fields, $layout = 'layouts.app')
    {
        $stub = File::get($this->stubPath . 'controller.stub');

        $modelVariable = Str::camel($modelName);
        $modelPluralVariable = Str::camel(Str::plural($modelName));

        // Get searchable and sortable fields
        $searchableFields = $this->getSearchableFields($fields);
        $sortableFields = $this->getSortableFields($fields);
        $searchableFieldsArray = $this->getFieldsAsArray($searchableFields);
        $sortableFieldsArray = $this->getFieldsAsArray($sortableFields);

        $replacements = [
            '{{ModelName}}' => $modelName,
            '{{modelVariable}}' => $modelVariable,
            '{{modelPluralVariable}}' => $modelPluralVariable,
            '{{requestName}}' => $modelName . 'Request',
            '{{searchableFieldsArray}}' => $searchableFieldsArray,
            '{{sortableFieldsArray}}' => $sortableFieldsArray,
            '{{layout}}' => $layout,
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

    protected function generateViews($modelName, $fields, $layout = 'layouts.app')
    {
        // Ensure layout exists
        $this->ensureLayoutExists($layout);

        $viewPath = resource_path('views/' . Str::lower(Str::plural($modelName)));

        if (!File::exists($viewPath)) {
            File::makeDirectory($viewPath, 0755, true);
        }

        $views = ['index', 'create', 'edit', 'show'];
        $generatedViews = [];

        foreach ($views as $view) {
            $stub = File::get($this->getStubPath("views/{$view}.stub"));
            $content = $this->replaceViewPlaceholders($stub, $modelName, $fields, $layout);

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

    protected function replaceViewPlaceholders($stub, $modelName, $fields, $layout = 'layouts.app')
    {
        $modelVariable = Str::camel($modelName);
        $modelPluralVariable = Str::camel(Str::plural($modelName));
        $routePrefix = Str::lower(Str::plural($modelName));

        // Filter fields by visibility
        $createFields = $this->filterFieldsByVisibility($fields, 'create');
        $editFields = $this->filterFieldsByVisibility($fields, 'edit');
        $indexFields = $this->filterFieldsByVisibility($fields, 'index');
        $showFields = $this->filterFieldsByVisibility($fields, 'show');

        // Generate searchable and sortable field lists
        $searchableFields = $this->getSearchableFields($fields);
        $sortableFields = $this->getSortableFields($fields);

        // Generate searchable/sortable fields arrays for controller
        $searchableFieldsArray = $this->getFieldsAsArray($searchableFields);
        $sortableFieldsArray = $this->getFieldsAsArray($sortableFields);

        $replacements = [
            '{{ModelName}}' => $modelName,
            '{{modelVariable}}' => $modelVariable,
            '{{modelPluralVariable}}' => $modelPluralVariable,
            '${{modelVariable}}' => '$' . $modelVariable,
            '${{modelPluralVariable}}' => '$' . $modelPluralVariable,
            '{{routePrefix}}' => $routePrefix,
            '{{createFormFields}}' => $this->generateFormFields($createFields, 'create', $modelVariable),
            '{{editFormFields}}' => $this->generateFormFields($editFields, 'edit', $modelVariable),
            '{{tableHeaders}}' => $this->generateTableHeaders($indexFields),
            '{{tableData}}' => $this->generateTableData($indexFields, $modelVariable),
            '{{showFields}}' => $this->generateShowFields($showFields, $modelVariable),
            '{{searchableFields}}' => $searchableFields,
            '{{sortableFields}}' => $sortableFields,
            '{{searchableFieldsArray}}' => $searchableFieldsArray,
            '{{sortableFieldsArray}}' => $sortableFieldsArray,
            '{{layout}}' => $layout,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }

    protected function filterFieldsByVisibility($fields, $page)
    {
        return array_filter($fields, function ($field) use ($page) {
            return isset($field['visibility'][$page]) && $field['visibility'][$page];
        });
    }

    protected function getSearchableFields($fields)
    {
        $searchable = [];
        foreach ($fields as $field) {
            if (isset($field['table']['searchable']) && $field['table']['searchable']) {
                $searchable[] = $field['name'];
            }
        }
        return implode(',', $searchable);
    }

    protected function getSortableFields($fields)
    {
        $sortable = [];
        foreach ($fields as $field) {
            if (isset($field['table']['sortable']) && $field['table']['sortable']) {
                $sortable[] = $field['name'];
            }
        }
        return implode(',', $sortable);
    }

    protected function getFieldsAsArray($commaSeparatedFields)
    {
        if (empty($commaSeparatedFields)) {
            return '';
        }
        
        $fields = explode(',', $commaSeparatedFields);
        $quotedFields = array_map(function($field) {
            return "'" . trim($field) . "'";
        }, $fields);
        
        return implode(', ', $quotedFields);
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
            $isSortable = isset($field['table']['sortable']) && $field['table']['sortable'];
            
            if ($isSortable) {
                $html .= "            <th>\n";
                $html .= "                <a href=\"{{ request()->fullUrlWithQuery(['sort' => '{$field['name']}', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}\" class=\"text-decoration-none text-dark\">\n";
                $html .= "                    {$label}\n";
                $html .= "                    @if(request('sort') === '{$field['name']}')\n";
                $html .= "                        <i class=\"fas fa-sort-{{ request('direction') === 'asc' ? 'up' : 'down' }}\"></i>\n";
                $html .= "                    @else\n";
                $html .= "                        <i class=\"fas fa-sort text-muted\"></i>\n";
                $html .= "                    @endif\n";
                $html .= "                </a>\n";
                $html .= "            </th>\n";
            } else {
                $html .= "            <th>{$label}</th>\n";
            }
        }
        return $html;
    }

    protected function generateTableData($fields, $modelVariable)
    {
        $html = '';
        foreach ($fields as $field) {
            $formatter = $field['table']['formatter'] ?? 'text';
            
            switch ($formatter) {
                case 'badge':
                    $html .= $this->generateBadgeColumn($field, $modelVariable);
                    break;
                case 'date':
                    $html .= "                <td>{{ \${$modelVariable}->{$field['name']}?->format('M d, Y') }}</td>\n";
                    break;
                case 'boolean':
                    $badgeTexts = $field['table']['badgeTexts'] ?? [];
                    $badgeColors = $field['table']['badgeColors'] ?? [];
                    
                    // Get text labels or use defaults
                    $trueText = $badgeTexts['1'] ?? ($badgeTexts[1] ?? 'Yes');
                    $falseText = $badgeTexts['0'] ?? ($badgeTexts[0] ?? 'No');
                    
                    // Get colors or use defaults
                    $trueColor = $badgeColors['1'] ?? ($badgeColors[1] ?? 'success');
                    $falseColor = $badgeColors['0'] ?? ($badgeColors[0] ?? 'secondary');
                    
                    $html .= "                <td>\n";
                    $html .= "                    @if(\${$modelVariable}->{$field['name']})\n";
                    $html .= "                        <span class=\"badge bg-{$trueColor}\">{$trueText}</span>\n";
                    $html .= "                    @else\n";
                    $html .= "                        <span class=\"badge bg-{$falseColor}\">{$falseText}</span>\n";
                    $html .= "                    @endif\n";
                    $html .= "                </td>\n";
                    break;
                case 'link':
                    $html .= "                <td><a href=\"{{ route('{{routePrefix}}.show', \${$modelVariable}) }}\" class=\"text-primary\">{{ \${$modelVariable}->{$field['name']} }}</a></td>\n";
                    break;
                default:
                    $html .= "                <td>{{ \${$modelVariable}->{$field['name']} }}</td>\n";
            }
        }
        return $html;
    }

    protected function generateBadgeColumn($field, $modelVariable)
    {
        $badgeColors = $field['table']['badgeColors'] ?? [];
        $badgeTexts = $field['table']['badgeTexts'] ?? [];
        
        // If no colors and no texts, use simple badge
        if (empty($badgeColors) && empty($badgeTexts)) {
            return "                <td><span class=\"badge bg-primary\">{{ \${$modelVariable}->{$field['name']} }}</span></td>\n";
        }
        
        $html = "                <td>\n";
        $html .= "                    @php\n";
        
        // Add color mapping if provided
        if (!empty($badgeColors)) {
            $html .= "                        \$colorMap = " . var_export($badgeColors, true) . ";\n";
            $html .= "                        \$color = \$colorMap[\${$modelVariable}->{$field['name']}] ?? 'secondary';\n";
        } else {
            $html .= "                        \$color = 'secondary';\n";
        }
        
        // Add text mapping if provided
        if (!empty($badgeTexts)) {
            $html .= "                        \$textMap = " . var_export($badgeTexts, true) . ";\n";
            $html .= "                        \$text = \$textMap[\${$modelVariable}->{$field['name']}] ?? \${$modelVariable}->{$field['name']};\n";
        } else {
            $html .= "                        \$text = \${$modelVariable}->{$field['name']};\n";
        }
        
        $html .= "                    @endphp\n";
        $html .= "                    <span class=\"badge bg-{{ \$color }}\">{{ \$text }}</span>\n";
        $html .= "                </td>\n";
        
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

    protected function ensureLayoutExists($layoutName)
    {
        $viewPath = resource_path('views/' . str_replace('.', '/', $layoutName) . '.blade.php');
        
        if (!File::exists($viewPath)) {
            // Create directory if needed
            $directory = dirname($viewPath);
            if (!File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true);
            }
            
            // Copy default layout stub
            $stub = File::get($this->getStubPath('layout.stub'));
            File::put($viewPath, $stub);
        }
    }
}
