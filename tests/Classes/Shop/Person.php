<?php

declare(strict_types=1);

namespace Brick\JsonMapper\Tests\Classes\Shop;

final class Person
{
    public function __construct(
        public readonly int $id,
        public readonly string $firstname,
        public readonly string $lastname,
    ) {
    }
}
