<?php

declare(strict_types=1);

namespace Brick\JsonMapper\NameMapper;

use Brick\JsonMapper\NameMapper;

final class CamelCaseToSnakeCaseMapper implements NameMapper
{
    public function mapPropertyName(string $propertyName): string
    {
        return preg_replace_callback(
            '/[A-Z]/',
            fn (array $matches) => '_' . strtolower($matches[0]),
            $propertyName,
        );
    }
}
