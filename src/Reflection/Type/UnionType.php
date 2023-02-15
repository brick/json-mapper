<?php

declare(strict_types=1);

namespace Brick\JsonMapper\Reflection\Type;

use Brick\JsonMapper\JsonMapperException;
use Stringable;

/**
 * Represents a combination of supported types.
 * For simplicity, even a single type is represented as a union type with a single element.
 *
 * @internal This class is not part of the public API, and may change without notice.
 */
final class UnionType implements Stringable
{
    public readonly bool $allowsInt;
    public readonly bool $allowsFloat;
    public readonly bool $allowsString;
    public readonly bool $allowsTrue;
    public readonly bool $allowsFalse;
    public readonly bool $allowsNull;
    public readonly bool $allowsRawArray;
    public readonly bool $allowsRawObject;
    public readonly bool $allowsMixed;

    /**
     * @var ClassType[]
     */
    public readonly array $classTypes;

    /**
     * At most one enum type per backed type (int, string) is allowed in a union.
     *
     * @var EnumType[]
     */
    public readonly array $enumTypes;

    /**
     * At most one ArrayType is allowed in a union.
     */
    public readonly ?ArrayType $arrayType;

    /**
     * @param (SimpleType|ClassType|EnumType|ArrayType)[] $types
     *
     * @throws JsonMapperException
     */
    public function __construct(
        public readonly array $types,
    ) {
        $this->ensureNotEmpty();
        $this->ensureNoDuplicateTypes();

        $simpleTypes = array_map(
            fn (SimpleType $type): string => $type->name,
            $this->filterTypes(SimpleType::class),
        );

        $containsSimpleType = static fn (string $type): bool => in_array($type, $simpleTypes, true);

        $hasInt = $containsSimpleType('int');
        $hasFloat = $containsSimpleType('float');
        $hasString = $containsSimpleType('string');
        $hasBool = $containsSimpleType('bool');
        $hasTrue = $containsSimpleType('true');
        $hasFalse = $containsSimpleType('false');
        $hasNull = $containsSimpleType('null');
        $hasArray = $containsSimpleType('array');
        $hasObject = $containsSimpleType('object');
        $hasMixed = $containsSimpleType('mixed');

        // We accept mapping int to float for two reasons:
        //  - JSON does not have separate int & float types
        //  - PHP accepts int to float implicit conversion, even with strict types enabled
        $this->allowsInt = $hasInt || $hasFloat || $hasMixed;
        $this->allowsFloat = $hasFloat || $hasMixed;
        $this->allowsString = $hasString || $hasMixed;
        $this->allowsTrue = $hasTrue || $hasBool || $hasMixed;
        $this->allowsFalse = $hasFalse || $hasBool || $hasMixed;
        $this->allowsNull = $hasNull || $hasMixed;
        $this->allowsRawArray = $hasArray || $hasMixed;
        $this->allowsRawObject = $hasObject || $hasMixed;
        $this->allowsMixed = $hasMixed;

        $this->classTypes = $this->filterTypes(ClassType::class);
        $this->enumTypes = $this->filterTypes(EnumType::class);

        $hasIntBackedEnum = false;
        $hasStringBackedEnum = false;

        foreach ($this->enumTypes as $enumType) {
            if ($enumType->isIntBacked) {
                if ($hasInt) {
                    throw new JsonMapperException('Cannot use int-backed enum together with "int" in a union.');
                }
                if ($hasIntBackedEnum) {
                    throw new JsonMapperException('At most one int-backed enum is allowed in a union.');
                }
                $hasIntBackedEnum = true;
            }

            if ($enumType->isStringBacked) {
                if ($hasString) {
                    throw new JsonMapperException('Cannot use string-backed enum together with "string" in a union.');
                }
                if ($hasStringBackedEnum) {
                    throw new JsonMapperException('At most one string-backed enum is allowed in a union.');
                }
                $hasStringBackedEnum = true;
            }
        }

        $arrayTypes = $this->filterTypes(ArrayType::class);

        $this->arrayType = match (count($arrayTypes)) {
            0 => null,
            1 => $arrayTypes[0],
            default => throw new JsonMapperException('At most one typed array "[]" is allowed in a union.'),
        };

        if ($hasMixed && count($types) > 1) {
            throw new JsonMapperException('Cannot use "mixed" together with other types in a union.');
        }

        if ($hasBool) {
            if ($hasTrue) {
                throw new JsonMapperException('Type "true" is redundant with "bool".');
            }
            if ($hasFalse) {
                throw new JsonMapperException('Type "false" is redundant with "bool".');
            }
        }

        if ($hasTrue && $hasFalse) {
            throw new JsonMapperException('Type contains both "true" and "false", "bool" should be used instead.');
        }

        if ($hasArray && $this->arrayType !== null) {
            throw new JsonMapperException('Cannot use untyped "array" together with a typed array "[]" in a union.');
        }

        if ($hasObject && $this->classTypes) {
            throw new JsonMapperException('Cannot use untyped "object" or "stdClass" together with a typed class in a union.');
        }
    }

    private function ensureNotEmpty(): void
    {
        if (! $this->types) {
            throw new JsonMapperException('Union type cannot be empty.');
        }
    }

    private function ensureNoDuplicateTypes(): void
    {
        $typeStrings = array_map(
            fn (Stringable $type) => (string) $type,
            $this->types,
        );

        if (count($typeStrings) !== count(array_unique($typeStrings))) {
            foreach (array_count_values($typeStrings) as $type => $count) {
                if ($count !== 1) {
                    throw new JsonMapperException(sprintf('Duplicate type "%s" is redundant.', $type));
                }
            }
        }
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $className
     *
     * @return list<T>
     */
    private function filterTypes(string $className): array
    {
        return array_values(array_filter(
            $this->types,
            fn (SimpleType|ClassType|EnumType|ArrayType $type) => $type instanceof $className,
        ));
    }

    public function __toString(): string
    {
        return implode('|', array_map(
            fn (Stringable $value) => (string) $value,
            $this->types,
        ));
    }
}
