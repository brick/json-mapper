<?php

declare(strict_types=1);

namespace Brick\JsonMapper\Reflection;

final class TypeToken
{
    public function __construct(
        public readonly string $value,
        public readonly int $offset,
        public readonly bool $isNamedType,
    ) {
    }
}
