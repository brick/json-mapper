<?php

declare(strict_types=1);

namespace Brick\JsonMapper\Tests\Classes\Shop;

final class OrderReport
{
    /**
     * @param (Person|Company)[] $customers
     */
    public function __construct(
        public readonly array $customers,
        public readonly int $numberOfOrders,
        public readonly float $totalAmount,
    ) {
    }
}
