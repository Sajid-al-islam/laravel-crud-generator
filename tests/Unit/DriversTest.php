<?php

declare(strict_types=1);

namespace SajidUlIslam\CrudGenerator\Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use SajidUlIslam\CrudGenerator\CrudDefinition;
use SajidUlIslam\CrudGenerator\Drivers\BladeDriver;
use SajidUlIslam\CrudGenerator\Enums\Stack;
use SajidUlIslam\CrudGenerator\GeneratorFactory;
use SajidUlIslam\CrudGenerator\Services\ConfigLoader;
use SajidUlIslam\CrudGenerator\Services\FieldParser;
use SajidUlIslam\CrudGenerator\Services\FileWriter;
use SajidUlIslam\CrudGenerator\Services\RouteRegistrar;
use SajidUlIslam\CrudGenerator\Services\StubManager;
use SajidUlIslam\CrudGenerator\Tests\TestCase;

class DriversTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Use a temp directory for app/ so we don't pollute the project
        $tmp = sys_get_temp_dir().'/crud-generator-test-'.uniqid();
        mkdir($tmp.'/app/Models', 0755, true);
        mkdir($tmp.'/app/Http/Controllers', 0755, true);
        mkdir($tmp.'/app/Http/Requests', 0755, true);
        mkdir($tmp.'/app/Http/Resources', 0755, true);
        mkdir($tmp.'/app/Services', 0755, true);
        mkdir($tmp.'/app/Repositories', 0755, true);
        mkdir($tmp.'/app/Repositories/Contracts', 0755, true);
        mkdir($tmp.'/app/Providers', 0755, true);
        mkdir($tmp.'/app/Livewire', 0755, true);
        mkdir($tmp.'/app/Filament/Resources', 0755, true);
        mkdir($tmp.'/app/Nova', 0755, true);
        mkdir($tmp.'/database/migrations', 0755, true);
        mkdir($tmp.'/routes', 0755, true);
        mkdir($tmp.'/resources/views', 0755, true);
        mkdir($tmp.'/resources/js/pages', 0755, true);
        mkdir($tmp.'/resources/js/types', 0755, true);
        file_put_contents($tmp.'/routes/web.php', "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n");
        file_put_contents($tmp.'/routes/api.php', "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n");
        $this->tmp = $tmp;
    }

    private string $tmp = '';

    public function test_factory_resolves_all_stacks(): void
    {
        foreach (Stack::values() as $key) {
            $driver = GeneratorFactory::make($key);
            $this->assertInstanceOf(\SajidUlIslam\CrudGenerator\Contracts\GeneratorDriver::class, $driver);
        }
    }

    public function test_factory_throws_for_unknown_stack(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        GeneratorFactory::make('foobar');
    }

    public function test_blade_driver_generates_files(): void
    {
        $this->bindPaths();

        $driver = app(BladeDriver::class);
        $definition = $this->definition('blade', 'Post', 'posts');

        $result = $driver->generate($definition);

        $this->assertArrayHasKey('controller', $result);
        $this->assertArrayHasKey('model', $result);
        $this->assertSame('created', $result['controller']['status']);
        $this->assertFileExists($result['controller']['path']);
        $this->assertStringContainsString('class PostController', file_get_contents($result['controller']['path']));
    }

    public function test_blade_driver_with_service_and_repository(): void
    {
        $this->bindPaths();

        $driver = app(BladeDriver::class);
        $definition = $this->definition('blade', 'Post', 'posts', withService: true, withRepository: true);

        $result = $driver->generate($definition);

        $this->assertArrayHasKey('service', $result);
        $this->assertArrayHasKey('repository', $result);
        $this->assertArrayHasKey('repository_interface', $result);
        $this->assertFileExists($result['service']['path']);
        $this->assertFileExists($result['repository']['path']);
        $this->assertStringContainsString('PostRepositoryInterface', file_get_contents($result['repository']['path']));
    }

    public function test_force_flag_overwrites_existing(): void
    {
        $this->bindPaths();
        $first = $this->definition('blade', 'Post', 'posts', force: false);
        app(BladeDriver::class)->generate($first);
        $path = $first->modelName.'.php';
        file_put_contents(app_path('Models/'.$path), "// user wrote this\n");

        $second = $this->definition('blade', 'Post', 'posts', force: true);
        $result = app(BladeDriver::class)->generate($second);

        $this->assertSame('overwritten', $result['model']['status']);
    }

    public function test_without_force_skips_existing(): void
    {
        $this->bindPaths();
        $first = $this->definition('blade', 'Post', 'posts', force: false);
        app(BladeDriver::class)->generate($first);
        file_put_contents(app_path('Models/Post.php'), "// user wrote this\n");

        $second = $this->definition('blade', 'Post', 'posts', force: false);
        $result = app(BladeDriver::class)->generate($second);

        $this->assertSame('skipped', $result['model']['status']);
    }

    private function bindPaths(): void
    {
        // Override the app path base for the test by manually creating files in temp
        // We re-use the real app_path() but make sure required subdirs exist.
        foreach ([
            'Models', 'Http/Controllers', 'Http/Requests', 'Http/Resources', 'Services',
            'Repositories', 'Repositories/Contracts', 'Livewire', 'Filament/Resources', 'Nova',
        ] as $d) {
            $p = app_path($d);
            if (! is_dir($p)) {
                mkdir($p, 0755, true);
            }
        }
    }

    private function definition(
        string $stack,
        string $model,
        string $table,
        bool $withService = false,
        bool $withRepository = false,
        bool $force = false,
    ): CrudDefinition {
        return new CrudDefinition(
            modelName: $model,
            tableName: $table,
            fields: [
                ['name' => 'title', 'type' => 'string', 'validation' => 'required|max:255', 'nullable' => false, 'searchable' => true, 'sortable' => true],
                ['name' => 'body', 'type' => 'text', 'validation' => 'nullable', 'nullable' => true, 'searchable' => false, 'sortable' => false],
            ],
            stack: $stack,
            withService: $withService,
            withRepository: $withRepository,
            options: ['force' => $force, 'with_migration' => true, 'register_routes' => false],
        );
    }
}
