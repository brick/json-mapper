<?php

declare(strict_types=1);

namespace Brick\JsonMapper\Tests\Classes\Shop;

final readonly class OrderReport
{
    /**
     * @param (Person|Company)[] $customers
     */
    public function __construct(
        public array $customers,
        public int $numberOfOrders,
        public float $totalAmount,
    ) {
    }
}
