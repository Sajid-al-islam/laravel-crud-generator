<?php

declare(strict_types=1);

namespace SajidUlIslam\CrudGenerator\Services;

use Illuminate\Filesystem\Filesystem;
use SajidUlIslam\CrudGenerator\CrudDefinition;
use SajidUlIslam\CrudGenerator\Enums\Stack;

class RouteRegistrar
{
    public function __construct(
        protected Filesystem $files,
    ) {}

    /**
     * Append a route to the appropriate routes file.
     * Returns the path that was modified.
     */
    public function register(CrudDefinition $definition, ?string $routeLine = null, ?string $useStatement = null): ?string
    {
        if (! $definition->options['register_routes'] ?? true) {
            return null;
        }

        $routeLine ??= $this->defaultRouteLine($definition);
        $useStatement ??= $this->defaultUseStatement($definition);

        $stack = $definition->stackEnum();

        $routesFile = match ($stack) {
            Stack::Api, Stack::Filament, Stack::Nova => base_path('routes/api.php'),
            Stack::Livewire => base_path('routes/web.php'),
            default => base_path('routes/web.php'),
        };

        if (! $this->files->exists($routesFile)) {
            $this->files->put($routesFile, "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n");
        }

        $content = $this->files->get($routesFile);

        if ($useStatement !== '' && ! str_contains($content, $useStatement)) {
            $content = $this->insertUseStatement($content, $useStatement);
            $this->files->put($routesFile, $content);
        }

        $content = $this->files->get($routesFile);
        if (! str_contains($content, $routeLine)) {
            $this->files->append($routesFile, "\n".$routeLine."\n");
        }

        return $routesFile;
    }

    public function defaultRouteLine(CrudDefinition $definition): string
    {
        $stack = $definition->stackEnum();
        $routePrefix = $definition->routePrefix();
        $controller = $definition->modelName.'Controller';

        return match ($stack) {
            Stack::Api => "Route::apiResource('{$routePrefix}', \\App\\Http\\Controllers\\Api\\{$controller}::class);",
            Stack::Livewire => $this->livewireRouteLines($definition),
            default => "Route::resource('{$routePrefix}', \\App\\Http\\Controllers\\{$controller}::class);",
        };
    }

    public function defaultUseStatement(CrudDefinition $definition): string
    {
        $stack = $definition->stackEnum();

        if ($stack === Stack::Api) {
            return "use App\\Http\\Controllers\\Api\\{$definition->modelName}Controller;";
        }

        if ($stack === Stack::Livewire) {
            return $this->livewireUseStatements($definition);
        }

        return "use App\\Http\\Controllers\\{$definition->modelName}Controller;";
    }

    protected function livewireRouteLines(CrudDefinition $definition): string
    {
        $prefix = $definition->routePrefix();
        $model = $definition->modelName;
        $lower = $definition->modelVariable();
        $plural = $definition->modelNamePlural();
        $ns = 'App\\Livewire\\'.$plural;

        $lines = [
            "Route::get('/{$prefix}', \\{$ns}\\Index::class)->name('{$prefix}.index');",
            "Route::get('/{$prefix}/create', \\{$ns}\\Create::class)->name('{$prefix}.create');",
            "Route::get('/{$prefix}/{{$lower}}', \\{$ns}\\Show::class)->name('{$prefix}.show');",
            "Route::get('/{$prefix}/{{$lower}}/edit', \\{$ns}\\Edit::class)->name('{$prefix}.edit');",
        ];

        return implode("\n", $lines);
    }

    protected function livewireUseStatements(CrudDefinition $definition): string
    {
        $plural = $definition->modelNamePlural();
        $lines = [
            "use App\\Livewire\\{$plural}\\Index;",
            "use App\\Livewire\\{$plural}\\Create;",
            "use App\\Livewire\\{$plural}\\Show;",
            "use App\\Livewire\\{$plural}\\Edit;",
        ];

        return implode("\n", $lines);
    }

    protected function insertUseStatement(string $content, string $useStatement): string
    {
        $statements = explode("\n", $useStatement);
        $existing = $content;

        if (preg_match('/<\?php\s*(.*?)(?=\n\?>|\n[a-zA-Z]|\z)/s', $existing, $m)) {
            $prefix = substr($existing, 0, strpos($existing, $m[0]) + strlen('<?php'));
            $body = $m[1];
        } else {
            $prefix = "<?php\n";
            $body = "\n".$existing;
        }

        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '' || str_contains($body, $stmt)) {
                continue;
            }
            $body = preg_replace(
                '/((?:^|\n)\s*use [^;]+;\s*\n)+/s',
                "$0$stmt;\n",
                $body,
                1
            );
            if (! str_contains($body, $stmt)) {
                $body = "\n".$stmt.";\n".ltrim($body);
            }
        }

        return $prefix.$body;
    }
}
