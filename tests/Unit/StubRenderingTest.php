<?php

declare(strict_types=1);

namespace SajidUlIslam\CrudGenerator\Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use SajidUlIslam\CrudGenerator\CrudDefinition;
use SajidUlIslam\CrudGenerator\Drivers\BladeDriver;
use SajidUlIslam\CrudGenerator\Drivers\FilamentDriver;
use SajidUlIslam\CrudGenerator\Drivers\LivewireDriver;
use SajidUlIslam\CrudGenerator\Drivers\ReactDriver;
use SajidUlIslam\CrudGenerator\Drivers\VueDriver;
use SajidUlIslam\CrudGenerator\Drivers\SvelteDriver;
use SajidUlIslam\CrudGenerator\Services\FieldParser;
use SajidUlIslam\CrudGenerator\Services\FileWriter;
use SajidUlIslam\CrudGenerator\Services\StubManager;
use SajidUlIslam\CrudGenerator\Tests\TestCase;

class StubRenderingTest extends TestCase
{
    public function test_blade_stub_renders_valid_php(): void
    {
        $driver = app(BladeDriver::class);
        $definition = $this->definition('Post', 'posts', 'blade');

        $result = $driver->generate($definition);

        $controller = file_get_contents($result['controller']['path']);

        // Should NOT contain any unreplaced placeholders
        $this->assertDoesNotMatchRegularExpression('/\{\{\s*\w+\s*\}\}/', $controller, 'Unreplaced placeholder found');
        // Must contain the model name in the class definition
        $this->assertStringContainsString('class PostController', $controller);
    }

    public function test_react_stub_renders(): void
    {
        $driver = app(ReactDriver::class);
        $definition = $this->definition('Post', 'posts', 'react');

        $result = $driver->generate($definition);

        $this->assertArrayHasKey('controller', $result);
        $controller = file_get_contents($result['controller']['path']);
        $this->assertStringContainsString('use Inertia\\Inertia;', $controller);
        $this->assertStringContainsString("Inertia::render('Posts/Index'", $controller);
    }

    public function test_vue_stub_renders(): void
    {
        $driver = app(VueDriver::class);
        $definition = $this->definition('Post', 'posts', 'vue');

        $result = $driver->generate($definition);

        $this->assertArrayHasKey('controller', $result);
    }

    public function test_svelte_stub_renders(): void
    {
        $driver = app(SvelteDriver::class);
        $definition = $this->definition('Post', 'posts', 'svelte');

        $result = $driver->generate($definition);

        $this->assertArrayHasKey('controller', $result);
    }

    public function test_livewire_stub_renders(): void
    {
        $driver = app(LivewireDriver::class);
        $definition = $this->definition('Post', 'posts', 'livewire');

        $result = $driver->generate($definition);

        $this->assertArrayHasKey('index', $result);
        $this->assertStringContainsString('use Livewire\\Component', file_get_contents($result['index']['path']));
    }

    public function test_filament_stub_renders(): void
    {
        $driver = app(FilamentDriver::class);
        $definition = $this->definition('Post', 'posts', 'filament');

        $result = $driver->generate($definition);

        $this->assertArrayHasKey('resource', $result);
    }

    public function test_no_unreplaced_placeholders_in_any_stub(): void
    {
        $stacks = ['blade', 'api', 'react', 'vue', 'svelte', 'livewire', 'filament'];
        foreach ($stacks as $stack) {
            $tmp = sys_get_temp_dir().'/crud-test-'.uniqid();
            mkdir($tmp, 0755, true);
            $dirs = [
                'app/Models', 'app/Http/Controllers', 'app/Http/Requests',
                'app/Http/Resources', 'app/Services', 'app/Repositories',
                'app/Repositories/Contracts', 'app/Livewire',
                'app/Filament/Resources', 'app/Nova', 'database/migrations',
                'resources/views', 'resources/js/pages', 'resources/js/types',
            ];
            foreach ($dirs as $d) {
                @mkdir($tmp.'/'.$d, 0755, true);
            }

            $class = '\\SajidUlIslam\\CrudGenerator\\Drivers\\'.ucfirst($stack).'Driver';
            $driver = new $class(
                app(\SajidUlIslam\CrudGenerator\Services\StubManager::class),
                new Filesystem,
                app(\SajidUlIslam\CrudGenerator\Services\FileWriter::class),
                app(\SajidUlIslam\CrudGenerator\Services\FieldParser::class),
                app(\SajidUlIslam\CrudGenerator\Services\ConfigLoader::class),
                app(\SajidUlIslam\CrudGenerator\Services\RouteRegistrar::class),
            );

            $definition = $this->definition('Post', 'posts', $stack);
            $result = $driver->generate($definition);

            foreach ($result as $key => $value) {
                if (is_array($value) && isset($value['path']) && is_file($value['path'])) {
                    $content = file_get_contents($value['path']);
                    $unreplaced = [];
                    preg_match_all('/\{\{\s*\w+\s*\}\}/', $content, $m);
                    if (! empty($m[0])) {
                        $unreplaced = array_unique($m[0]);
                    }
                    $this->assertEmpty(
                        $unreplaced,
                        "Stack [{$stack}] file [{$key}] has unreplaced placeholders: ".implode(', ', $unreplaced)
                    );
                }
                if (is_array($value) && isset($value[0]) && is_array($value[0])) {
                    foreach ($value as $file) {
                        if (isset($file['path']) && is_file($file['path'])) {
                            $content = file_get_contents($file['path']);
                            preg_match_all('/\{\{\s*\w+\s*\}\}/', $content, $m);
                            $this->assertEmpty($m[0], "Stack [{$stack}] file [{$key}] has unreplaced placeholders");
                        }
                    }
                }
            }
        }
    }

    private function definition(string $model, string $table, string $stack): CrudDefinition
    {
        return new CrudDefinition(
            modelName: $model,
            tableName: $table,
            fields: [
                ['name' => 'title', 'type' => 'string', 'validation' => 'required|max:255', 'nullable' => false, 'searchable' => true, 'sortable' => true],
                ['name' => 'body', 'type' => 'text', 'validation' => 'nullable', 'nullable' => true, 'searchable' => false, 'sortable' => false],
            ],
            stack: $stack,
            options: ['force' => true, 'with_migration' => true, 'register_routes' => false],
        );
    }
}
