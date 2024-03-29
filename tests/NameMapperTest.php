<?php

declare(strict_types=1);

namespace Brick\JsonMapper\Tests\Reflection;

use Brick\JsonMapper\NameMapper;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NameMapperTest extends TestCase
{
    #[DataProvider('providerNameMapper')]
    public function testNameMapper(NameMapper $nameMapper, string $propertyName, string $expected): void
    {
        self::assertSame($expected, $nameMapper->mapName($propertyName));
    }

    /**
     * @return Generator<array{NameMapper, string, string}>
     */
    public static function providerNameMapper(): Generator
    {
        foreach (self::providerNullMapper() as $index => $test) {
            yield 'NullMapper#' . $index => [new NameMapper\NullMapper(), ...$test];
        }

        foreach (self::providerCamelCaseToSnakeCaseMapper() as $index => $test) {
            yield 'CamelCaseToSnakeCaseMapper#' . $index => [new NameMapper\CamelCaseToSnakeCaseMapper(), ...$test];
        }

        foreach (self::providerSnakeCaseToCamelCaseMapper() as $index => $test) {
            yield 'SnakeCaseToCamelCaseMapper#' . $index => [new NameMapper\SnakeCaseToCamelCaseMapper(), ...$test];
        }
    }

    /**
     * @return array<array{string, string}>
     */
    private static function providerNullMapper(): array
    {
        return [
            ['foo', 'foo'],
            ['foo_bar', 'foo_bar'],
            ['fooBar', 'fooBar'],
            ['FooBar', 'FooBar'],
        ];
    }

    /**
     * @return array<array{string, string}>
     */
    private static function providerCamelCaseToSnakeCaseMapper(): array
    {
        return [
            ['fooBar', 'foo_bar'],
            ['FooBar', '_foo_bar'],
        ];
    }

    /**
     * @return array<array{string, string}>
     */
    private static function providerSnakeCaseToCamelCaseMapper(): array
    {
        return [
            ['foo_bar', 'fooBar'],
            ['_foo_bar', 'FooBar'],
        ];
    }
}
