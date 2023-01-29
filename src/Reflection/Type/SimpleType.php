<?php

declare(strict_types=1);

namespace Brick\JsonMapper\Reflection\Type;

use Stringable;

final class SimpleType implements Stringable
{
    /**
     * @psalm-param 'int'|'float'|'string'|'bool'|'true'|'false'|'null'|'array'|'object'|'mixed' $type
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
