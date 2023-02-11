<?php

declare(strict_types=1);

namespace Brick\JsonMapper\Reflection\Type;

use BackedEnum;
use InvalidArgumentException;
use Stringable;

final class EnumType implements Stringable
{
    /**
     * @param class-string<BackedEnum> $name
     */
    public function __construct(
        public readonly string $name,
        public readonly bool $isIntBacked = false,
        public readonly bool $isStringBacked = false,
    ) {
        if (! ($this->isIntBacked xor $this->isStringBacked)) {
            throw new InvalidArgumentException('EnumType must be either int or string backed.');
        }
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
