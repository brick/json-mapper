<?php

declare(strict_types=1);

namespace Brick\JsonMapper\Tests\Classes\Shop;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
}
