<?php

declare(strict_types=1);

namespace Brick\JsonMapper\NameMapper;

use Brick\JsonMapper\NameMapper;

final class NullMapper implements NameMapper
{
    public function mapName(string $name): string
    {
        return $name;
    }
}
