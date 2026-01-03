<?php

declare(strict_types=1);

namespace Brick\JsonMapper\Tests\Classes\Shop;

final readonly class Person
{
    public function __construct(
        public int $id,
        public string $firstname,
        public string $lastname,
    ) {
    }
}
