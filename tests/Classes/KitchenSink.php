<?php

declare(strict_types=1);

namespace Brick\JsonMapper\Tests\Classes;

use Brick\JsonMapper\Tests\Attributes\ExpectException;
use Brick\JsonMapper\Tests\Attributes\ExpectParameterType;
use Countable;
use Some\Namespace;
use stdClass;
use Traversable;

final class KitchenSink
{
    /**
     * @param bool|false[] $c
     * @param (int|string)[] $d
     * @param ((int)|string|((int|bool[][]))[])[] $e
     * @param (int|string) $f
     * @param  (int|(string | float[]))  $g
     */
    public function scalarTypes(
        #[ExpectParameterType('int')]
        int $a,

        #[ExpectParameterType('string|int')]
        int|string $b,

        #[ExpectParameterType('bool|false[]')]
        mixed $c,

        #[ExpectParameterType('(int|string)[]')]
        array $d,

        #[ExpectParameterType('(int|string|(int|bool[][])[])[]')]
        array $e,

        #[ExpectParameterType('int|string')]
        mixed $f,

        #[ExpectParameterType('int|string|float[]')]
        mixed $g,
    ): void {
    }

    /**
     * @param KitchenSink[]|null $d
     * @param KitchenSink[]|\Closure|null $e
     * @param KitchenSink[]|Foo\Bar|Namespace\Bar\Baz|null $f
     */
    public function classTypes(
        #[ExpectParameterType('Brick\JsonMapper\Tests\Classes\KitchenSink')]
        KitchenSink $a,

        #[ExpectParameterType('Brick\JsonMapper\Tests\Classes\KitchenSink|null')]
        ?KitchenSink $b,

        #[ExpectParameterType('Brick\JsonMapper\Tests\Classes\KitchenSink|Brick\JsonMapper\Tests\Classes\Foo\Bar|null')]
        KitchenSink|Foo\Bar|null $c,

        #[ExpectParameterType('Brick\JsonMapper\Tests\Classes\KitchenSink[]|null')]
        ?array $d,

        #[ExpectParameterType('Brick\JsonMapper\Tests\Classes\KitchenSink[]|Closure|null')]
        mixed $e,

        #[ExpectParameterType('Brick\JsonMapper\Tests\Classes\KitchenSink[]|Brick\JsonMapper\Tests\Classes\Foo\Bar|Some\Namespace\Bar\Baz|null')]
        mixed $f,
    ): void {
    }

    /**
     * @param array $b
     * @param int|((string|array)[]) $c
     */
    public function arrayTypes(
        #[ExpectException('Parameter $a of Brick\JsonMapper\Tests\Classes\KitchenSink::arrayTypes() contains type "array" which is not allowed by default.')]
        #[ExpectParameterType('array', ['allowUntypedArrays' => true])]
        array $a,

        #[ExpectException('Parameter $b of Brick\JsonMapper\Tests\Classes\KitchenSink::arrayTypes() contains type "array" which is not allowed by default.')]
        #[ExpectParameterType('array', ['allowUntypedArrays' => true])]
        mixed $b,

        #[ExpectException('Parameter $c of Brick\JsonMapper\Tests\Classes\KitchenSink::arrayTypes() contains type "array" which is not allowed by default.')]
        #[ExpectParameterType('int|(string|array)[]', ['allowUntypedArrays' => true])]
        mixed $c,
    ): void {
    }

    /**
     * @param mixed[] $b
     * @param ((int|mixed[])[]|string)[] $c
     */
    public function mixedTypes(
        #[ExpectException('Parameter $a of Brick\JsonMapper\Tests\Classes\KitchenSink::mixedTypes() contains type "mixed" which is not allowed by default.')]
        #[ExpectParameterType('mixed', ['allowMixed' => true])]
        mixed $a,

        #[ExpectException('Parameter $b of Brick\JsonMapper\Tests\Classes\KitchenSink::mixedTypes() contains type "mixed" which is not allowed by default.')]
        #[ExpectParameterType('mixed[]', ['allowMixed' => true])]
        array $b,

        #[ExpectException('Parameter $c of Brick\JsonMapper\Tests\Classes\KitchenSink::mixedTypes() contains type "mixed" which is not allowed by default.')]
        #[ExpectParameterType('((int|mixed[])[]|string)[]', ['allowMixed' => true])]
        array $c,
    ): void {
    }

    /**
     * @param (int|stdClass)[] $c
     * @param KitchenSink|object $d
     */
    public function objectTypes(
        #[ExpectException('Parameter $a of Brick\JsonMapper\Tests\Classes\KitchenSink::objectTypes() contains type "object" which is not allowed by default.')]
        #[ExpectParameterType('object', ['allowUntypedObjects' => true])]
        object $a,

        #[ExpectException('Parameter $b of Brick\JsonMapper\Tests\Classes\KitchenSink::objectTypes() contains type "stdClass" which is not allowed by default.')]
        #[ExpectParameterType('object', ['allowUntypedObjects' => true])]
        stdClass $b,

        #[ExpectException('Parameter $c of Brick\JsonMapper\Tests\Classes\KitchenSink::objectTypes() contains type "stdClass" which is not allowed by default.')]
        #[ExpectParameterType('(int|object)[]', ['allowUntypedObjects' => true])]
        array $c,

        #[ExpectException(
            'Parameter $d of Brick\JsonMapper\Tests\Classes\KitchenSink::objectTypes() contains an invalid type: ' .
            'Cannot use untyped "object" or "stdClass" together with a typed class in a union.',
            ['allowUntypedObjects' => true],
        )]
        object $d,
    ): void {
    }

    /**
     * @param (int|mixed)[] $a
     * @param true|false $b
     * @param (bool|true)[] $c
     * @param (bool|int|false)[][] $d
     * @param (int|string|int)[] $e
     */
    public function redundantTypes(
        #[ExpectException(
            'Parameter $a of Brick\JsonMapper\Tests\Classes\KitchenSink::redundantTypes() contains an invalid type: ' .
            'Cannot use "mixed" together with other types in a union.',
            ['allowMixed' => true],
        )]
        array $a,

        #[ExpectException(
            'Parameter $b of Brick\JsonMapper\Tests\Classes\KitchenSink::redundantTypes() contains an invalid type: ' .
            'Type contains both "true" and "false", "bool" should be used instead.',
        )]
        bool $b,

        #[ExpectException(
            'Parameter $c of Brick\JsonMapper\Tests\Classes\KitchenSink::redundantTypes() contains an invalid type: ' .
            'Type "true" is redundant with "bool".',
        )]
        array $c,

        #[ExpectException(
            'Parameter $d of Brick\JsonMapper\Tests\Classes\KitchenSink::redundantTypes() contains an invalid type: ' .
            'Type "false" is redundant with "bool".',
        )]
        array $d,

        #[ExpectException(
            'Parameter $e of Brick\JsonMapper\Tests\Classes\KitchenSink::redundantTypes() contains an invalid type: ' .
            'Duplicate type "int" is redundant.',
        )]
        array $e,
    ): void {
    }

    /**
     * @param KitchenSink[]|string[]|null $a
     * @param (int|bool[]|string[])[] $b
     * @param array|string[] $c
     */
    public function multipleArrayTypes(
        #[ExpectException(
            'Parameter $a of Brick\JsonMapper\Tests\Classes\KitchenSink::multipleArrayTypes() contains an invalid type: ' .
            'At most one typed array "[]" is allowed in a union.',
        )]
        ?array $a,

        #[ExpectException(
            'Parameter $b of Brick\JsonMapper\Tests\Classes\KitchenSink::multipleArrayTypes() contains an invalid type: ' .
            'At most one typed array "[]" is allowed in a union.',
        )]
        array $b,

        #[ExpectException(
            'Parameter $c of Brick\JsonMapper\Tests\Classes\KitchenSink::multipleArrayTypes() contains an invalid type: ' .
            'Cannot use untyped "array" together with a typed array "[]" in a union.',
            ['allowUntypedArrays' => true],
        )]
        array $c,
    ): void {
    }

    /**
     * @param static $b
     * @param parent $c
     * @param void $d
     * @param never $e
     */
    public function disallowedBuiltinTypes(
        #[ExpectException('Parameter $a of Brick\JsonMapper\Tests\Classes\KitchenSink::disallowedBuiltinTypes() contains type "self" which is not allowed.')]
        self $a,

        #[ExpectException('Parameter $b of Brick\JsonMapper\Tests\Classes\KitchenSink::disallowedBuiltinTypes() contains type "static" which is not allowed.')]
        mixed $b,

        #[ExpectException('Parameter $c of Brick\JsonMapper\Tests\Classes\KitchenSink::disallowedBuiltinTypes() contains type "parent" which is not allowed.')]
        mixed $c,

        #[ExpectException('Parameter $d of Brick\JsonMapper\Tests\Classes\KitchenSink::disallowedBuiltinTypes() contains type "void" which is not allowed.')]
        mixed $d,

        #[ExpectException('Parameter $e of Brick\JsonMapper\Tests\Classes\KitchenSink::disallowedBuiltinTypes() contains type "never" which is not allowed.')]
        mixed $e,

        #[ExpectException('Parameter $f of Brick\JsonMapper\Tests\Classes\KitchenSink::disallowedBuiltinTypes() contains type "iterable" which is not allowed.')]
        iterable $f,

        #[ExpectException('Parameter $g of Brick\JsonMapper\Tests\Classes\KitchenSink::disallowedBuiltinTypes() contains type "callable" which is not allowed.')]
        callable $g,
    ): void {
    }

    /**
     * @param $b
     * @param  $c
     */
    public function noTypes(
        #[ExpectException('Parameter $a of Brick\JsonMapper\Tests\Classes\KitchenSink::noTypes() must have a type or be documented with @param.')]
        $a,

        #[ExpectException('Parameter $b of Brick\JsonMapper\Tests\Classes\KitchenSink::noTypes() has an empty @param type.')]
        $b,

        #[ExpectException('Parameter $c of Brick\JsonMapper\Tests\Classes\KitchenSink::noTypes() has an empty @param type.')]
        $c,
    ): void {
    }

    /**
     * @param int[] $a
     * @param string[] $a
     */
    public function multipleDocblockTypes(
        #[ExpectException('Parameter $a of Brick\JsonMapper\Tests\Classes\KitchenSink::multipleDocblockTypes() has multiple @param types.')]
        mixed $a,
    ): void {
    }

    /**
     * @param (Countable&Traversable)|null $b
     */
    public function intersectionTypes(
        #[ExpectException('Parameter $a of Brick\JsonMapper\Tests\Classes\KitchenSink::intersectionTypes() cannot have an intersection type.')]
        Countable&Traversable $a,

        #[ExpectException('Parameter $b of Brick\JsonMapper\Tests\Classes\KitchenSink::intersectionTypes() has an invalid @param type: Unexpected "&" at offset 10.')]
        Countable|null $b
    ): void {
    }

    /**
     * @param$a
     * @param(int)$b
     * @param int| $c
     * @param int|[] $d
     * @param (int $e
     * @param int) $f
     * @param () $g
     * @param ()[] $h
     */
    public function parseErrors(
        #[ExpectException('Parameter $a of Brick\JsonMapper\Tests\Classes\KitchenSink::parseErrors() has a badly-formatted @param type.')]
        mixed $a,

        #[ExpectException('Parameter $b of Brick\JsonMapper\Tests\Classes\KitchenSink::parseErrors() has a badly-formatted @param type.')]
        mixed $b,

        #[ExpectException(
            'Parameter $c of Brick\JsonMapper\Tests\Classes\KitchenSink::parseErrors() has an invalid @param type: ' .
            'Expected named type or "(", found end of string.',
        )]
        mixed $c,

        #[ExpectException(
            'Parameter $d of Brick\JsonMapper\Tests\Classes\KitchenSink::parseErrors() has an invalid @param type: ' .
            'Expected named type or "(", found "[]" at offset 4.',
        )]
        mixed $d,

        #[ExpectException(
            'Parameter $e of Brick\JsonMapper\Tests\Classes\KitchenSink::parseErrors() has an invalid @param type: ' .
            'Expected ")" or "|" or "[]", found end of string.',
        )]
        mixed $e,

        #[ExpectException(
            'Parameter $f of Brick\JsonMapper\Tests\Classes\KitchenSink::parseErrors() has an invalid @param type: ' .
            'Expected end of string or "|" or "[]", found ")" at offset 3.',
        )]
        mixed $f,

        #[ExpectException(
            'Parameter $g of Brick\JsonMapper\Tests\Classes\KitchenSink::parseErrors() has an invalid @param type: ' .
            'Expected named type or "(", found ")" at offset 1.',
        )]
        mixed $g,

        #[ExpectException(
            'Parameter $h of Brick\JsonMapper\Tests\Classes\KitchenSink::parseErrors() has an invalid @param type: ' .
            'Expected named type or "(", found ")" at offset 1.',
        )]
        mixed $h,
    ): void {
    }
}
