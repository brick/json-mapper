<?php

declare(strict_types=1);

namespace Brick\JsonMapper\Tests\Classes\Shop;

final class Order
{
    public function __construct(
        public readonly int $id,
        public readonly Person|Company $customer,
        public readonly string $date,
        public readonly float $amount,
        public readonly OrderStatus $status,
    ) {
    }
}
