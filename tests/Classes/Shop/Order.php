<?php

declare(strict_types=1);

namespace Brick\JsonMapper\Tests\Classes\Shop;

final readonly class Order
{
    public function __construct(
        public int $id,
        public Person|Company $customer,
        public string $date,
        public float $amount,
        public OrderStatus $status,
    ) {
    }
}
