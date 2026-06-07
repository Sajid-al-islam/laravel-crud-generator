<?php

declare(strict_types=1);

namespace SajidUlIslam\CrudGenerator\Services;

class RelationDetector
{
    /**
     * Foreign-key suffix.
     */
    public const FK_SUFFIX = '_id';

    /**
     * Return relation hints for the given field list.
     *
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<int, array<string, string>>
     */
    public function detect(array $fields): array
    {
        $relations = [];
        foreach ($fields as $field) {
            $name = $field['name'] ?? null;
            if (! is_string($name)) {
                continue;
            }
            if (str_ends_with($name, self::FK_SUFFIX)) {
                $base = substr($name, 0, -strlen(self::FK_SUFFIX));
                $relations[] = [
                    'name' => $name,
                    'type' => 'belongsTo',
                    'related' => str($base)->studly()->toString(),
                    'method' => str($base)->camel()->toString(),
                ];
            }
        }

        return $relations;
    }
}
