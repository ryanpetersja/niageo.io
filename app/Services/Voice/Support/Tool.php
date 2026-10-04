<?php

namespace App\Services\Voice\Support;

/**
 * Small builder for Messages API tool definitions (JSON schema).
 */
final class Tool
{
    /**
     * @param  array<string, array>  $properties  JSON-schema property map
     * @param  string[]  $required
     */
    public static function make(string $name, string $description, array $properties = [], array $required = []): array
    {
        return [
            'name' => $name,
            'description' => $description,
            'input_schema' => [
                'type' => 'object',
                // An empty PHP array would encode as [] which is not a valid schema object.
                'properties' => empty($properties) ? new \stdClass() : $properties,
                'required' => array_values($required),
                'additionalProperties' => false,
            ],
        ];
    }

    public static function string(string $description, ?array $enum = null): array
    {
        $schema = ['type' => 'string', 'description' => $description];
        if ($enum !== null) {
            $schema['enum'] = array_values($enum);
        }

        return $schema;
    }

    public static function number(string $description): array
    {
        return ['type' => 'number', 'description' => $description];
    }

    public static function integer(string $description): array
    {
        return ['type' => 'integer', 'description' => $description];
    }

    public static function boolean(string $description): array
    {
        return ['type' => 'boolean', 'description' => $description];
    }

    public static function integerList(string $description): array
    {
        return ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => $description];
    }

    public static function stringList(string $description): array
    {
        return ['type' => 'array', 'items' => ['type' => 'string'], 'description' => $description];
    }
}
