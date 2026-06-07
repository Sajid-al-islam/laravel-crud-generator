<?php

declare(strict_types=1);

namespace SajidUlIslam\CrudGenerator\Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;
use SajidUlIslam\CrudGenerator\Services\ConfigLoader;
use SajidUlIslam\CrudGenerator\Services\StubManager;

class StubManagerTest extends TestCase
{
    public function test_resolve_returns_package_stub_when_no_custom(): void
    {
        $files = new Filesystem;
        $config = new ConfigLoader($files);
        $manager = new StubManager($files, $config);

        $path = $manager->resolve('controller.stub', 'blade');

        $this->assertStringContainsString('stubs/blade/controller.stub', $path);
        $this->assertFileExists($path);
    }

    public function test_render_substitutes_variables(): void
    {
        $files = new Filesystem;
        $config = new ConfigLoader($files);
        $manager = new StubManager($files, $config);

        $path = $manager->resolve('model.stub', 'shared');
        $output = $manager->render($path, [
            'modelName' => 'Post',
            'modelNamespace' => 'App\\Models',
            'tableName' => 'posts',
            'fillableFields' => "'title', 'body'",
        ]);

        $this->assertStringContainsString('class Post extends Model', $output);
        $this->assertStringContainsString("'title', 'body'", $output);
        $this->assertStringNotContainsString('{{ modelName }}', $output);
    }

    public function test_candidate_paths_includes_all_sources(): void
    {
        $files = new Filesystem;
        $config = new ConfigLoader($files);
        $manager = new StubManager($files, $config);

        $paths = $manager->candidatePaths('controller.stub', 'react');

        $this->assertGreaterThanOrEqual(2, count($paths));
        $this->assertStringEndsWith('/react/controller.stub', $paths[0]);
    }

    public function test_resolve_throws_for_missing_stub(): void
    {
        $files = new Filesystem;
        $config = new ConfigLoader($files);
        $manager = new StubManager($files, $config);

        $this->expectException(\RuntimeException::class);
        $manager->resolve('nonexistent.stub', 'react');
    }
}
