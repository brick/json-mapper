<?php

declare(strict_types=1);

namespace Brick\JsonMapper\NameMapper;

use Brick\JsonMapper\NameMapper;
use Override;

use function preg_replace_callback;
use function strtolower;

final class CamelCaseToSnakeCaseMapper implements NameMapper
{
    #[Override]
    public function mapName(string $name): string
    {
        $result = preg_replace_callback(
            '/[A-Z]/',
            fn (array $matches) => '_' . strtolower($matches[0]),
            $name,
        );

        assert($result !== null);

        return $result;
    }
}
