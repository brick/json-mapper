<?php

declare(strict_types=1);

namespace Brick\JsonMapper\Tests\Classes\Shop;

final class Company
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $companyNumber,
    ) {
    }
}
