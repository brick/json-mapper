<?php

declare(strict_types=1);

namespace Brick\JsonMapper\Reflection\Type;

use Stringable;

/**
 * @internal This class is not part of the public API, and may change without notice.
 */
final class ClassType implements Stringable
{
    /**
     * @param class-string $name
     */
    public function __construct(
        public readonly string $name,
    ) {
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
