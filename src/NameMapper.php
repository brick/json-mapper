<?php

declare(strict_types=1);

namespace Brick\JsonMapper;

interface NameMapper
{
    public function mapPropertyName(string $propertyName): string;
}
