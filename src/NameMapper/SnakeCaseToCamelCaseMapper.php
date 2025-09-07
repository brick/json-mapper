<?php

declare(strict_types=1);

namespace Brick\JsonMapper\NameMapper;

use Brick\JsonMapper\NameMapper;

use function preg_replace_callback;
use function strtoupper;

final class SnakeCaseToCamelCaseMapper implements NameMapper
{
    public function mapName(string $name): string
    {
        return preg_replace_callback(
            '/_([a-z])/',
            fn (array $matches) => strtoupper($matches[1]),
            $name,
        );
    }
}
