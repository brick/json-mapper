<?php

declare(strict_types=1);

namespace Brick\JsonMapper\Tests\Reflection;

use Brick\JsonMapper\JsonMapperException;
use Brick\JsonMapper\Reflection\TypeParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TypeParserTest extends TestCase
{
    #[DataProvider('providerParse')]
    public function testParse(string $type, array $expected): void
    {
        $typeParser = new TypeParser($type);
        self::assertSame($expected, $typeParser->parse());
    }

    public static function providerParse(): array
    {
        return [
            ['string', ['string']],
            ['string|null', ['string', 'null']],
            ['(string|null)[]', [['string', 'null']]],
            ['string|int[]', ['string', ['int']]],
            ['string|(int|null)[]', ['string', ['int', 'null']]],
            ['A|B|C', ['A', 'B', 'C']],
            ['A|B|C[]|D[]', ['A', 'B', ['C'], ['D']]],
            ['A|B|(C|D[])[]|E[]', ['A', 'B', ['C', ['D']], ['E']]],
            ['A|B|(C|D[])[][]|E[]', ['A', 'B', [['C', ['D']]], ['E']]],
            ['(A)|B', ['A', 'B']],
            ['int', ['int']],
            ['int[]', [['int']]],
            ['int[][]', [[['int']]]],
            ['(int)', ['int']],
            ['((int))', ['int']],
            ['(((int)))[]', [['int']]],
            ['(int)[]', [['int']]],
            ['(int)[][][]', [[[['int']]]]],
            ['((int)[])[]', [[['int']]]],
            ['((int|false)[])[]', [[['int', 'false']]]],
            ['((int|false)[]|null)[]', [[['int', 'false'], 'null']]],
            ['((int|false)[]|null)[]|true', [[['int', 'false'], 'null'], 'true']],
            ['string|(int|float)[]', ['string', ['int', 'float']]],
            ['int[]|(string|A|B|(int|float)[])[]|null', [['int'], ['string', 'A', 'B', ['int', 'float']], 'null']],
            ['(int)[]|(string|int)[]|float|string', [['int'], ['string', 'int'], 'float', 'string']],
        ];
    }

    #[DataProvider('providerParseInvalidType')]
    public function testParseInvalidType(string $type, string $exceptionMessage): void
    {
        $typeParser = new TypeParser($type);

        self::expectException(JsonMapperException::class);
        self::expectExceptionMessage($exceptionMessage);

        $typeParser->parse();
    }

    public static function providerParseInvalidType(): array
    {
        return [
            ['', 'Expected named type or "(", found end of string.'],
            [' ', 'Expected named type or "(", found end of string.'],
            ['A | ', 'Expected named type or "(", found end of string.'],
            ['?string', 'Unexpected "?" at offset 0.'],
            ['array<string>', 'Unexpected "<" at offset 5.'],
            ['|', 'Expected named type or "(", found "|" at offset 0.'],
            ['[]', 'Expected named type or "(", found "[]" at offset 0.'],
            ['()', 'Expected named type or "(", found ")" at offset 1.'],
            ['()[]', 'Expected named type or "(", found ")" at offset 1.'],
            ['(|)[]', 'Expected named type or "(", found "|" at offset 1.'],
            ['|[]', 'Expected named type or "(", found "|" at offset 0.'],
            [')', 'Expected named type or "(", found ")" at offset 0.'],
            ['(', 'Expected named type or "(", found end of string.'],
            [')[]', 'Expected named type or "(", found ")" at offset 0.'],
            ['(A)[', 'Char "[" is not followed by "]" at offset 3.'],
            ['(A)]', 'Char "]" is not preceded by "[" at offset 3.'],
            ['A A', 'Expected "|" or "[]", found "A" at offset 2.'],
            ['(A A)', 'Expected "|" or "[]" or ")", found "A" at offset 3.'],
            ['((A)A)', 'Expected "|" or "[]" or ")", found "A" at offset 4.'],
            ['(A)(A)', 'Expected "|" or "[]", found "(" at offset 3.'],
            ['((A)(A))', 'Expected "|" or "[]" or ")", found "(" at offset 4.'],
            ['A|', 'Expected named type or "(", found end of string.'],
            ['|B', 'Expected named type or "(", found "|" at offset 0.'],
            ['(A|)[]', 'Expected named type or "(", found ")" at offset 3.'],
            ['(|B)[]', 'Expected named type or "(", found "|" at offset 1.'],
            ['A||B', 'Expected named type or "(", found "|" at offset 2.'],
            ['(A[]', 'Expected ")" or "|" or "[]", found end of string.'],
            ['B)[]', 'Expected end of string or "|" or "[]", found ")" at offset 1.'],
            ['(A', 'Expected ")" or "|" or "[]", found end of string.'],
            ['B)', 'Expected end of string or "|" or "[]", found ")" at offset 1.'],
            ['A&B', 'Unexpected "&" at offset 1.'],
            ['A*B', 'Unexpected "*" at offset 1.'],
            ['A/B', 'Unexpected "/" at offset 1.'],
            ['A|B=|C', 'Unexpected "=" at offset 3.'],
            ['A|B|', 'Expected named type or "(", found end of string.'],
            ['((A)[]', 'Expected ")" or "|" or "[]", found end of string.'],
            ['(A))[]', 'Expected end of string or "|" or "[]", found ")" at offset 3.'],
        ];
    }
}
