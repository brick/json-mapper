<?php

declare(strict_types=1);

namespace Brick\JsonMapper\Reflection;

/**
 * @internal This class is not part of the public API, and may change without notice.
 */
final readonly class TypeToken
{
    public function __construct(
        public string $value,
        public int $offset,
        public bool $isNamedType,
    ) {
    }
}
