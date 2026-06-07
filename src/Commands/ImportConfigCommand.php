<?php

declare(strict_types=1);

namespace SajidUlIslam\CrudGenerator\Commands;

use Illuminate\Console\Command;
use RuntimeException;

class ImportConfigCommand extends Command
{
    protected $signature = 'crud:import-config
                            {file : Path to the JSON file to import}
                            {--target= : Destination file (default: crud-generator.json in project root)}';

    protected $description = 'Import and validate a crud-generator.json file into the project root.';

    public function handle(): int
    {
        $file = (string) $this->argument('file');

        if (! is_file($file)) {
            $this->error("File not found: {$file}");

            return self::FAILURE;
        }

        $raw = file_get_contents($file);
        $data = json_decode($raw, true);

        if (! is_array($data)) {
            $this->error('Invalid JSON: '.json_last_error_msg());

            return self::FAILURE;
        }

        $this->validateSchema($data);

        $target = (string) ($this->option('target') ?? base_path('crud-generator.json'));
        file_put_contents($target, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info("Configuration imported to {$target}");
        $this->line('');
        $this->line('Summary:');
        foreach ($data as $key => $value) {
            $this->line(sprintf('  - %-22s %s', $key, is_scalar($value) ? (string) $value : json_encode($value)));
        }

        return self::SUCCESS;
    }

    protected function validateSchema(array $data): void
    {
        if (isset($data['stack']) && ! is_string($data['stack'])) {
            throw new RuntimeException('`stack` must be a string.');
        }
        if (isset($data['imports']) && ! is_array($data['imports'])) {
            throw new RuntimeException('`imports` must be an object.');
        }
        if (isset($data['wrappers']) && ! is_array($data['wrappers'])) {
            throw new RuntimeException('`wrappers` must be an object.');
        }
    }
}
