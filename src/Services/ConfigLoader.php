<?php

declare(strict_types=1);

namespace SajidUlIslam\CrudGenerator\Services;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class ConfigLoader
{
    protected ?array $cached = null;

    public function __construct(
        protected Filesystem $files,
    ) {}

    /**
     * Path to crud-generator.json in the consumer project root.
     */
    public function configPath(): string
    {
        $configured = config('crud-generator.custom_config_path');

        return is_string($configured) && $configured !== ''
            ? $configured
            : base_path('crud-generator.json');
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if ($this->cached !== null) {
            return $this->cached;
        }

        $path = $this->configPath();
        if (! $this->files->exists($path)) {
            return $this->cached = [];
        }

        $json = $this->files->get($path);
        $data = json_decode($json, true);

        if (! is_array($data)) {
            throw new RuntimeException(
                "Invalid JSON in {$path}: ".json_last_error_msg()
            );
        }

        return $this->cached = $data;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $data = $this->all();

        return $data[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    public function reload(): void
    {
        $this->cached = null;
        $this->all();
    }
}
