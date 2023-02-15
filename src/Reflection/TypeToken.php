<?php

declare(strict_types=1);

namespace Brick\JsonMapper\Reflection;

/**
 * @internal This class is not part of the public API, and may change without notice.
 */
final class TypeToken
{
    public function __construct(
        public readonly string $value,
        public readonly int $offset,
        public readonly bool $isNamedType,
    ) {
    }
}
