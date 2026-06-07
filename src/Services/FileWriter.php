<?php

declare(strict_types=1);

namespace SajidUlIslam\CrudGenerator\Services;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;
use SajidUlIslam\CrudGenerator\CrudDefinition;

class FileWriter
{
    public function __construct(
        protected Filesystem $files,
    ) {}

    /**
     * Write a file with overwrite protection.
     *
     * @return array{path:string, status:string}  status: created|skipped|overwritten
     */
    public function write(string $destination, string $content, bool $force = false): array
    {
        $dir = dirname($destination);

        if (! $this->files->isDirectory($dir)) {
            $this->files->makeDirectory($dir, 0755, true);
        }

        if ($this->files->exists($destination)) {
            if (! $force) {
                return ['path' => $destination, 'status' => 'skipped'];
            }
            $this->files->put($destination, $content);

            return ['path' => $destination, 'status' => 'overwritten'];
        }

        $this->files->put($destination, $content);

        return ['path' => $destination, 'status' => 'created'];
    }

    /**
     * Build an aggregated result map for a CrudDefinition generation.
     *
     * @param  array<string, array{path:string,status:string}>  $files
     * @return array<string, mixed>
     */
    public function summarize(string $role, array $files): array
    {
        return [$role => array_values(array_map(fn ($f) => $f, $files))];
    }
}
