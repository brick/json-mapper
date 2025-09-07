<?php

declare(strict_types=1);

namespace Brick\JsonMapper\Tests\Reflection;

use Brick\JsonMapper\JsonMapper;
use Brick\JsonMapper\JsonMapperException;
use Brick\JsonMapper\OnExtraProperties;
use Brick\JsonMapper\OnMissingProperties;
use Brick\JsonMapper\Tests\Classes\Music\Album;
use Brick\JsonMapper\Tests\Classes\Music\Artist;
use Brick\JsonMapper\Tests\Classes\NoConstructor;
use Brick\JsonMapper\Tests\Classes\Shop\Company;
use Brick\JsonMapper\Tests\Classes\Shop\Order;
use Brick\JsonMapper\Tests\Classes\Shop\OrderReport;
use Brick\JsonMapper\Tests\Classes\Shop\OrderStatus;
use Brick\JsonMapper\Tests\Classes\Shop\Person;
use PHPUnit\Framework\TestCase;

final class JsonMapperTest extends TestCase
{
    public function testMapInvalidJson(): void
    {
        $jsonMapper = new JsonMapper();

        $this->expectException(JsonMapperException::class);
        $this->expectExceptionMessage('Invalid JSON data: Syntax error');

        $jsonMapper->map('xxx', Album::class);
    }

    public function testMapNonObject(): void
    {
        $jsonMapper = new JsonMapper();

        $this->expectException(JsonMapperException::class);
        $this->expectExceptionMessage('Unexpected JSON data: expected object, got array.');

        $jsonMapper->map('[]', Album::class);
    }

    public function testMapToInvalidClass(): void
    {
        $jsonMapper = new JsonMapper();

        $this->expectException(JsonMapperException::class);
        $this->expectExceptionMessage('Invalid class name: Brick\JsonMapper\Tests\Reflection\InvalidClassName');

        /** @psalm-suppress UndefinedClass */
        $jsonMapper->map('{}', InvalidClassName::class);
    }

    public function testMapToClassWithNoConstructor(): void
    {
        $jsonMapper = new JsonMapper();

        $this->expectException(JsonMapperException::class);
        $this->expectExceptionMessage('Class Brick\JsonMapper\Tests\Classes\NoConstructor must have a constructor.');

        $jsonMapper->map('{}', NoConstructor::class);
    }

    public function testMapAlbum(): void
    {
        $json = <<<'JSON'
            {
                "id": 1,
                "title": "The Dark Side of the Moon",
                "artist": {
                    "id": 2,
                    "name": "Pink Floyd",
                    "picture": "https://example.com/pink-floyd.jpg"
                },
                "contributors": [
                    {
                        "id": 3,
                        "name": "Roger Waters",
                        "picture": "https://example.com/roger-waters.jpg"
                    },
                    {
                        "id": 4,
                        "name": "David Gilmour",
                        "picture": null
                    }
                ],
                "picture": "https://example.com/the-dark-side-of-the-moon.jpg"
            }
            JSON;

        $jsonMapper = new JsonMapper();
        $album = $jsonMapper->map($json, Album::class);

        self::assertInstanceOf(Album::class, $album);

        self::assertSame(1, $album->id);
        self::assertSame('The Dark Side of the Moon', $album->title);

        self::assertSame(2, $album->artist->id);
        self::assertSame('Pink Floyd', $album->artist->name);
        self::assertSame('https://example.com/pink-floyd.jpg', $album->artist->picture);

        self::assertCount(2, $album->contributors);

        self::assertSame(3, $album->contributors[0]->id);
        self::assertSame('Roger Waters', $album->contributors[0]->name);
        self::assertSame('https://example.com/roger-waters.jpg', $album->contributors[0]->picture);

        self::assertSame(4, $album->contributors[1]->id);
        self::assertSame('David Gilmour', $album->contributors[1]->name);
        self::assertNull($album->contributors[1]->picture);

        self::assertSame('https://example.com/the-dark-side-of-the-moon.jpg', $album->picture);
    }

    public function testMapWithOnExtraPropertiesThrowException(): void
    {
        $json = <<<'JSON'
            {
                "id": 2,
                "name": "Pink Floyd",
                "picture": "https://example.com/pink-floyd.jpg",
                "extraProperty": null
            }
            JSON;

        $jsonMapper = new JsonMapper();

        $this->expectException(JsonMapperException::class);
        $this->expectExceptionMessage(
            'Unexpected property "extraProperty" in JSON data: ' .
            'Brick\JsonMapper\Tests\Classes\Music\Artist::__construct() does not have a corresponding ' .
            '$extraProperty parameter. If you want to allow extra properties, change the $onExtraProperties option.',
        );

        $jsonMapper->map($json, Artist::class);
    }

    public function testMapWithOnExtraPropertiesIgnore(): void
    {
        $json = <<<'JSON'
            {
                "id": 2,
                "name": "Pink Floyd",
                "picture": "https://example.com/pink-floyd.jpg",
                "extraProperty": null
            }
            JSON;

        $jsonMapper = new JsonMapper(onExtraProperties: OnExtraProperties::IGNORE);
        $artist = $jsonMapper->map($json, Artist::class);

        self::assertInstanceOf(Artist::class, $artist);

        self::assertSame(2, $artist->id);
        self::assertSame('Pink Floyd', $artist->name);
        self::assertSame('https://example.com/pink-floyd.jpg', $artist->picture);
    }

    public function testMapWithOnMissingPropertiesThrowException(): void
    {
        $json = <<<'JSON'
            {
                "id": 2,
                "name": "Pink Floyd"
            }
            JSON;

        $jsonMapper = new JsonMapper();

        $this->expectException(JsonMapperException::class);
        $this->expectExceptionMessage(
            'Missing property "picture" in JSON data. ' .
            'If you want to allow missing properties, change the $onMissingProperties option.',
        );

        $jsonMapper->map($json, Artist::class);
    }

    public function testMapWithOnMissingPropertiesSetNull(): void
    {
        $json = <<<'JSON'
            {
                "id": 2,
                "name": "Pink Floyd"
            }
            JSON;

        $jsonMapper = new JsonMapper(onMissingProperties: OnMissingProperties::SET_NULL);
        $artist = $jsonMapper->map($json, Artist::class);

        self::assertInstanceOf(Artist::class, $artist);

        self::assertSame(2, $artist->id);
        self::assertSame('Pink Floyd', $artist->name);
        self::assertNull($artist->picture);
    }

    public function testMapWithOnMissingPropertiesSetDefault(): void
    {
        $json = <<<'JSON'
            {
                "id": 2,
                "name": "Pink Floyd"
            }
            JSON;

        $jsonMapper = new JsonMapper(onMissingProperties: OnMissingProperties::SET_DEFAULT);
        $artist = $jsonMapper->map($json, Artist::class);

        self::assertInstanceOf(Artist::class, $artist);

        self::assertSame(2, $artist->id);
        self::assertSame('Pink Floyd', $artist->name);
        self::assertSame('default-picture', $artist->picture);
    }

    public function testMapOrderWithPersonCustomer(): void
    {
        $json = <<<'JSON'
            {
                "id": 1,
                "customer": {
                    "id": 2,
                    "firstname": "John",
                    "lastname": "Doe"
                },
                "date": "2022-01-01",
                "amount": 12,
                "status": "pending"
            }
            JSON;

        $jsonMapper = new JsonMapper();

        $order = $jsonMapper->map($json, Order::class);

        self::assertInstanceOf(Order::class, $order);

        self::assertSame(1, $order->id);
        self::assertSame('2022-01-01', $order->date);
        self::assertSame(12.0, $order->amount);
        self::assertSame(OrderStatus::PENDING, $order->status);

        self::assertInstanceOf(Person::class, $order->customer);

        self::assertSame(2, $order->customer->id);
        self::assertSame('John', $order->customer->firstname);
        self::assertSame('Doe', $order->customer->lastname);
    }

    public function testMapOrderWithCompanyCustomer(): void
    {
        $json = <<<'JSON'
            {
                "id": 2,
                "customer": {
                    "id": 3,
                    "name": "Acme Inc.",
                    "companyNumber": "1234-5678"
                },
                "date": "2022-02-03",
                "amount": 15,
                "status": "shipped"
            }
            JSON;

        $jsonMapper = new JsonMapper();

        $order = $jsonMapper->map($json, Order::class);

        self::assertInstanceOf(Order::class, $order);

        self::assertSame(2, $order->id);
        self::assertSame('2022-02-03', $order->date);
        self::assertSame(15.0, $order->amount);
        self::assertSame(OrderStatus::SHIPPED, $order->status);

        self::assertInstanceOf(Company::class, $order->customer);

        self::assertSame(3, $order->customer->id);
        self::assertSame('Acme Inc.', $order->customer->name);
        self::assertSame('1234-5678', $order->customer->companyNumber);
    }

    public function testMapOrderWithUnknownCustomer(): void
    {
        $json = <<<'JSON'
            {
                "id": 2,
                "customer": {
                    "id": 3,
                    "name": "Acme Inc.",
                    "unknownProperty": "XXX"
                },
                "date": "2022-02-03",
                "amount": 15,
                "status": "delivered"
            }
            JSON;

        $jsonMapper = new JsonMapper();

        $this->expectException(JsonMapperException::class);
        $this->expectExceptionMessage(
            "JSON object does not match any of the allowed PHP classes:\n" .
            " - Brick\JsonMapper\Tests\Classes\Shop\Person: Missing property \"firstname\" in JSON data.\n" .
            " - Brick\JsonMapper\Tests\Classes\Shop\Company: Missing property \"companyNumber\" in JSON data.",
        );

        $jsonMapper->map($json, Order::class);
    }

    public function testMapOrderReport(): void
    {
        $json = <<<'JSON'
            {
                "customers": [
                    {
                        "id": 2,
                        "firstname": "John",
                        "lastname": "Doe"
                    },
                    {
                        "id": 3,
                        "name": "Acme Inc.",
                        "companyNumber": "1234-5678"
                    }
                ],
                "numberOfOrders": 12,
                "totalAmount": 234.00
            }
            JSON;

        $jsonMapper = new JsonMapper();

        $orderReport = $jsonMapper->map($json, OrderReport::class);

        self::assertInstanceOf(OrderReport::class, $orderReport);

        self::assertSame(12, $orderReport->numberOfOrders);
        self::assertSame(234.0, $orderReport->totalAmount);

        self::assertInstanceOf(Person::class, $orderReport->customers[0]);

        self::assertSame(2, $orderReport->customers[0]->id);
        self::assertSame('John', $orderReport->customers[0]->firstname);
        self::assertSame('Doe', $orderReport->customers[0]->lastname);

        self::assertInstanceOf(Company::class, $orderReport->customers[1]);

        self::assertSame(3, $orderReport->customers[1]->id);
        self::assertSame('Acme Inc.', $orderReport->customers[1]->name);
        self::assertSame('1234-5678', $orderReport->customers[1]->companyNumber);
    }
}
