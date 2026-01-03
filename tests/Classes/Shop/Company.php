<?php

declare(strict_types=1);

namespace Brick\JsonMapper\Tests\Classes\Shop;

final readonly class Company
{
    public function __construct(
        public int $id,
        public string $name,
        public string $companyNumber,
    ) {
    }
}
