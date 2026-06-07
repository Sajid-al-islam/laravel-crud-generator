<?php

declare(strict_types=1);

namespace SajidUlIslam\CrudGenerator\Commands;

use Illuminate\Console\Command;
use SajidUlIslam\CrudGenerator\Enums\Stack;
use SajidUlIslam\CrudGenerator\GeneratorFactory;

class PublishStubsCommand extends Command
{
    protected $signature = 'crud:publish-stubs
                            {--stack= : Stack to publish stubs for (default: all)}
                            {--path= : Destination directory (default: base_path(crud-stubs))}';

    protected $description = 'Publish generator stubs to your project for customization.';

    public function handle(): int
    {
        $stack = (string) ($this->option('stack') ?? '');
        $path = (string) ($this->option('path') ?? base_path('crud-stubs'));

        if (! is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $stacks = $stack !== '' ? [$stack] : Stack::values();

        foreach ($stacks as $key) {
            if (! in_array($key, Stack::values(), true)) {
                $this->error("Unknown stack: {$key}");

                continue;
            }

            $driver = GeneratorFactory::make($key);
            $source = $driver->getStubPath();
            $destination = rtrim($path, '/').'/'.$key;

            if (! is_dir($source)) {
                $this->warn("Source stub path does not exist for [{$key}]: {$source}");

                continue;
            }

            $this->publishDirectory($source, $destination);
            $this->info("Stubs published to {$destination}");
        }

        return self::SUCCESS;
    }

    protected function publishDirectory(string $source, string $destination): void
    {
        if (! is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $target = $destination.DIRECTORY_SEPARATOR.$iterator->getSubPathName();
            if ($item->isDir()) {
                if (! is_dir($target)) {
                    mkdir($target, 0755, true);
                }
            } else {
                copy($item->getPathname(), $target);
            }
        }
    }
}
