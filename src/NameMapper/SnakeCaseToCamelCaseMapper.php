<?php

declare(strict_types=1);

namespace Brick\JsonMapper\NameMapper;

use Brick\JsonMapper\NameMapper;

class SnakeCaseToCamelCaseMapper implements NameMapper
{
    public function mapPropertyName(string $propertyName): string
    {
        return preg_replace_callback(
            '/_([a-z])/',
            fn (array $matches) => strtoupper($matches[1]),
            $propertyName,
        );
    }
}
