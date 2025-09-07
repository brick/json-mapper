<?php

declare(strict_types=1);

namespace Brick\JsonMapper\Tests\Reflection;

use Brick\JsonMapper\JsonMapperException;
use Brick\JsonMapper\Reflection\TypeToken;
use Brick\JsonMapper\Reflection\TypeTokenizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function array_map;

final class TypeTokenizerTest extends TestCase
{
    #[DataProvider('providerTokenize')]
    public function testTokenize(string $type, array $expected): void
    {
        $tokens = array_map(
            fn (TypeToken $token) => [
                $token->value,
                $token->offset,
                $token->isNamedType,
            ],
            TypeTokenizer::tokenize($type),
        );

        self::assertSame($expected, $tokens);
    }

    public static function providerTokenize(): array
    {
        return [
            ['', []],
            ['A', [
                ['A', 0, true],
            ]],
            ['A|', [
                ['A', 0, true],
                ['|', 1, false],
            ]],
            ['A|B', [
                ['A', 0, true],
                ['|', 1, false],
                ['B', 2, true],
            ]],
            ['Foo Bar', [
                ['Foo', 0, true],
                ['Bar', 4, true],
            ]],
            ['()[]', [
                ['(', 0, false],
                [')', 1, false],
                ['[]', 2, false],
            ]],
            ['Foo|(App\Foo\Bar|App\Foo_Baz)[]', [
                ['Foo', 0, true],
                ['|', 3, false],
                ['(', 4, false],
                ['App\Foo\Bar', 5, true],
                ['|', 16, false],
                ['App\Foo_Baz', 17, true],
                [')', 28, false],
                ['[]', 29, false],
            ]],
            [' Foo | ( App\Foo\Bar | App\Foo_Baz ) [] ', [
                ['Foo', 1, true],
                ['|', 5, false],
                ['(', 7, false],
                ['App\Foo\Bar', 9, true],
                ['|', 21, false],
                ['App\Foo_Baz', 23, true],
                [')', 35, false],
                ['[]', 37, false],
            ]],
        ];
    }

    #[DataProvider('providerTokenizeInvalidType')]
    public function testTokenizeInvalidType(string $type, string $exceptionMessage): void
    {
        self::expectException(JsonMapperException::class);
        self::expectExceptionMessage($exceptionMessage);

        TypeTokenizer::tokenize($type);
    }

    public static function providerTokenizeInvalidType(): array
    {
        return [
            ['=', 'Unexpected "=" at offset 0.'],
            ['==', 'Unexpected "==" at offset 0.'],
            ['=A|B', 'Unexpected "=" at offset 0.'],
            ['==A|B', 'Unexpected "==" at offset 0.'],
            ['A|=B', 'Unexpected "=" at offset 2.'],
            ['A|==B', 'Unexpected "==" at offset 2.'],
            ['A|B=', 'Unexpected "=" at offset 3.'],
            ['A|B==', 'Unexpected "==" at offset 3.'],
            ['array<string>', 'Unexpected "<" at offset 5.'],
            ['(Foo&Bar)|null', 'Unexpected "&" at offset 4.'],
            ['[', 'Char "[" is not followed by "]" at offset 0.'],
            [']', 'Char "]" is not preceded by "[" at offset 0.'],
            ['A[B', 'Char "[" is not followed by "]" at offset 1'],
            ['A]B', 'Char "]" is not preceded by "[" at offset 1'],
        ];
    }
}
