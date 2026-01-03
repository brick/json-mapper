<?php

declare(strict_types=1);

namespace Brick\JsonMapper\Reflection\Type;

use Override;
use Stringable;

/**
 * @internal This class is not part of the public API, and may change without notice.
 */
final class SimpleType implements Stringable
{
    public function __construct(
        public readonly string $name,
    ) {
    }

    #[Override]
    public function __toString(): string
    {
        return $this->name;
    }
}
