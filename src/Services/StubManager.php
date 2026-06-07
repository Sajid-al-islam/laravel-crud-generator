<?php

declare(strict_types=1);

namespace SajidUlIslam\CrudGenerator\Services;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class StubManager
{
    public function __construct(
        protected Filesystem $files,
        protected ConfigLoader $config,
    ) {}

    /**
     * Resolve a stub file by priority:
     * 1. Project crud-generator.json `customStubsPath`
     * 2. Published `crud-stubs/{stack}/` in project root
     * 3. Package built-in stubs
     *
     * @return string Absolute path to the stub.
     */
    public function resolve(string $stubName, string $stack): string
    {
        $candidates = $this->candidatePaths($stubName, $stack);

        foreach ($candidates as $path) {
            if ($this->files->exists($path)) {
                return $path;
            }
        }

        throw new RuntimeException(
            "Stub [{$stubName}] not found for stack [{$stack}]. ".
            'Searched: '.implode(', ', $candidates)
        );
    }

    /**
     * @return array<int, string>
     */
    public function candidatePaths(string $stubName, string $stack): array
    {
        $custom = $this->config->get('customStubsPath');
        $default = base_path('crud-stubs');

        $paths = [];

        if (is_string($custom) && $custom !== '') {
            $paths[] = rtrim($custom, '/').'/'.$stack.'/'.$stubName;
        }

        $paths[] = $default.'/'.$stack.'/'.$stubName;
        $paths[] = $this->packageStubPath($stack).'/'.$stubName;

        return $paths;
    }

    public function packageStubPath(string $stack): string
    {
        return dirname(__DIR__, 2).'/stubs/'.$stack;
    }

    /**
     * Render a stub with the given variables.
     *
     * @param  array<string, string|int|bool|null>  $variables
     */
    public function render(string $stubPath, array $variables): string
    {
        if (! $this->files->exists($stubPath)) {
            throw new RuntimeException("Stub file does not exist: {$stubPath}");
        }

        $contents = $this->files->get($stubPath);

        $search = [];
        $replace = [];
        foreach ($variables as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value);
            }
            $search[] = '{{ '.$key.' }}';
            $replace[] = (string) $value;
        }

        return str_replace($search, $replace, $contents);
    }

    /**
     * Resolve a stub and render it in one call.
     *
     * @param  array<string, string|int|bool|null>  $variables
     */
    public function resolveAndRender(string $stubName, string $stack, array $variables): string
    {
        return $this->render($this->resolve($stubName, $stack), $variables);
    }

    /**
     * Compute the absolute destination path for a generated file
     * based on a relative path under the project root.
     */
    public function destinationPath(string $relative): string
    {
        return base_path(ltrim($relative, '/'));
    }
}
