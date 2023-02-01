<?php

declare(strict_types=1);

namespace Brick\JsonMapper\NameMapper;

use Brick\JsonMapper\NameMapper;

class NullMapper implements NameMapper
{
    public function mapPropertyName(string $propertyName): string
    {
        return $propertyName;
    }
}
