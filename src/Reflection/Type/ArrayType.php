<?php

declare(strict_types=1);

namespace Brick\JsonMapper\Reflection\Type;

use Stringable;

/**
 * @internal This class is not part of the public API, and may change without notice.
 */
final class ArrayType implements Stringable
{
    public function __construct(
        public readonly UnionType $type,
    ) {
    }

    public function __toString(): string
    {
        if (count($this->type->types) === 1) {
            return $this->type . '[]';
        }

        return '(' . $this->type . ')[]';
    }
}
