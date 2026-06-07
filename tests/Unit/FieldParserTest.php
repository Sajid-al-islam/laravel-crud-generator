<?php

declare(strict_types=1);

namespace SajidUlIslam\CrudGenerator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SajidUlIslam\CrudGenerator\Services\FieldParser;
use SajidUlIslam\CrudGenerator\Services\RelationDetector;

class FieldParserTest extends TestCase
{
    protected FieldParser $parser;

    protected function setUp(): void
    {
        $this->parser = new FieldParser;
    }

    public function test_typescript_type_mapping(): void
    {
        $this->assertSame('number', $this->parser->typescriptType('integer'));
        $this->assertSame('number', $this->parser->typescriptType('bigInteger'));
        $this->assertSame('number', $this->parser->typescriptType('decimal'));
        $this->assertSame('boolean', $this->parser->typescriptType('boolean'));
        $this->assertSame('string', $this->parser->typescriptType('date'));
        $this->assertSame('string', $this->parser->typescriptType('string'));
    }

    public function test_ts_interface_body(): void
    {
        $body = $this->parser->tsInterfaceBody([
            ['name' => 'title', 'type' => 'string', 'nullable' => false],
            ['name' => 'views', 'type' => 'integer', 'nullable' => true],
        ]);

        $this->assertStringContainsString('title: string;', $body);
        $this->assertStringContainsString('views: number | null;', $body);
        $this->assertStringContainsString('created_at: string;', $body);
    }

    public function test_migration_field_line_string(): void
    {
        $line = $this->parser->migrationFieldLine(['name' => 'title', 'type' => 'string']);
        $this->assertStringContainsString("string('title')", $line);
    }

    public function test_migration_field_line_nullable(): void
    {
        $line = $this->parser->migrationFieldLine(['name' => 'bio', 'type' => 'text', 'nullable' => true]);
        $this->assertStringContainsString("text('bio')", $line);
        $this->assertStringContainsString('->nullable()', $line);
    }

    public function test_fillable_array(): void
    {
        $out = $this->parser->fillableArray([
            ['name' => 'title', 'type' => 'string'],
            ['name' => 'body', 'type' => 'text'],
        ]);
        $this->assertStringContainsString("'title'", $out);
        $this->assertStringContainsString("'body'", $out);
    }

    public function test_validation_rules(): void
    {
        $out = $this->parser->validationRules([
            ['name' => 'title', 'type' => 'string', 'validation' => 'required|max:255'],
            ['name' => 'body', 'type' => 'text'],
        ]);
        $this->assertStringContainsString("'title' => 'required|max:255'", $out);
        $this->assertStringContainsString("'body' => 'nullable'", $out);
    }

    public function test_relation_detector_finds_foreign_keys(): void
    {
        $detector = new RelationDetector;
        $rels = $detector->detect([
            ['name' => 'user_id', 'type' => 'bigInteger'],
            ['name' => 'title', 'type' => 'string'],
        ]);

        $this->assertCount(1, $rels);
        $this->assertSame('user_id', $rels[0]['name']);
        $this->assertSame('belongsTo', $rels[0]['type']);
        $this->assertSame('User', $rels[0]['related']);
        $this->assertSame('user', $rels[0]['method']);
    }

    public function test_filament_form_fields(): void
    {
        $out = $this->parser->filamentFormFields([
            ['name' => 'title', 'type' => 'string', 'validation' => 'required'],
            ['name' => 'is_published', 'type' => 'boolean'],
            ['name' => 'published_at', 'type' => 'datetime'],
        ]);
        $this->assertStringContainsString("TextInput::make('Title')", $out);
        $this->assertStringContainsString("Toggle::make('Is Published')", $out);
        $this->assertStringContainsString("DateTimePicker::make('Published At')", $out);
    }

    public function test_filament_table_columns(): void
    {
        $out = $this->parser->filamentTableColumns([
            ['name' => 'title', 'type' => 'string'],
            ['name' => 'is_published', 'type' => 'boolean'],
            ['name' => 'body', 'type' => 'text'],
        ]);
        $this->assertStringContainsString("TextColumn::make('Title')", $out);
        $this->assertStringContainsString("IconColumn::make('Is Published')", $out);
        $this->assertStringContainsString("TextColumn::make('Body')->limit(50)", $out);
    }
}
