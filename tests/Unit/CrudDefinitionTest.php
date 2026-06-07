<?php

declare(strict_types=1);

namespace SajidUlIslam\CrudGenerator\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SajidUlIslam\CrudGenerator\CrudDefinition;

class CrudDefinitionTest extends TestCase
{
    public function test_basic_construction(): void
    {
        $def = new CrudDefinition(
            modelName: 'Post',
            tableName: 'posts',
            fields: [['name' => 'title', 'type' => 'string']],
            stack: 'react',
        );

        $this->assertSame('Post', $def->modelName);
        $this->assertSame('posts', $def->tableName);
        $this->assertSame('Posts', $def->modelNamePlural());
        $this->assertSame('post', $def->modelNameLower());
        $this->assertSame('posts', $def->modelNameLowerPlural());
        $this->assertSame('post', $def->modelNameSnake());
        $this->assertSame('post', $def->modelNameCamel());
        $this->assertSame('posts', $def->routePrefix());
    }

    public function test_invalid_stack_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new CrudDefinition(
            modelName: 'Post',
            tableName: 'posts',
            fields: [],
            stack: 'invalid-stack',
        );
    }

    public function test_empty_model_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new CrudDefinition(
            modelName: '',
            tableName: 'posts',
            fields: [],
            stack: 'blade',
        );
    }

    public function test_repository_without_service_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new CrudDefinition(
            modelName: 'Post',
            tableName: 'posts',
            fields: [],
            stack: 'blade',
            withService: false,
            withRepository: true,
        );
    }

    public function test_repository_with_service_is_ok(): void
    {
        $def = new CrudDefinition(
            modelName: 'Post',
            tableName: 'posts',
            fields: [],
            stack: 'api',
            withService: true,
            withRepository: true,
        );

        $this->assertTrue($def->withService);
        $this->assertTrue($def->withRepository);
    }

    public function test_variables_contains_required_keys(): void
    {
        $def = new CrudDefinition(
            modelName: 'Post',
            tableName: 'posts',
            fields: [['name' => 'title', 'type' => 'string']],
            stack: 'react',
        );

        $vars = $def->variables();
        $this->assertSame('Post', $vars['modelName']);
        $this->assertSame('posts', $vars['tableName']);
        $this->assertSame('Post', $vars['namespace']);
        $this->assertSame('App\\Models', $vars['modelNamespace']);
        $this->assertSame('App\\Services', $vars['serviceNamespace']);
        $this->assertSame('react', $vars['stack']);
    }

    public function test_stack_enum(): void
    {
        $def = new CrudDefinition('Post', 'posts', [], 'react');
        $this->assertSame('react', $def->stackEnum()->value);
        $this->assertSame('React (Inertia + shadcn/ui)', $def->stackEnum()->label());
    }
}
