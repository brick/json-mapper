<?php

declare(strict_types=1);

namespace Brick\JsonMapper\Reflection;

use BackedEnum;
use Brick\JsonMapper\JsonMapperException;
use Brick\JsonMapper\Reflection\Type\ArrayType;
use Brick\JsonMapper\Reflection\Type\ClassType;
use Brick\JsonMapper\Reflection\Type\EnumType;
use Brick\JsonMapper\Reflection\Type\SimpleType;
use Brick\JsonMapper\Reflection\Type\UnionType;
use Brick\Reflection\ImportResolver;
use LogicException;
use ReflectionEnum;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;
use stdClass;
use UnitEnum;

/**
 * @internal This class is not part of the public API, and may change without notice.
 */
final class Reflector
{
    private const ALLOWED_BUILTIN_TYPES = [
        'int',
        'float',
        'string',
        'bool',
        'true',
        'false',
        'null',
        'array',
        'object',
        'mixed',
    ];

    private const DISALLOWED_BUILTIN_TYPES = [
        'self',
        'static',
        'parent',
        'void',
        'never',
        'iterable',
        'callable',
    ];

    public function __construct(
        public readonly bool $allowUntypedArrays = false,
        public readonly bool $allowUntypedObjects = false,
        public readonly bool $allowMixed = false,
    ) {
    }

    /**
     * Cache of parameter types, indexed by cache key.
     *
     * The cache key is generated from the class name (if any), function name, and parameter name.
     * Parameter types of closures are not cached.
     *
     * @var array<string, UnionType>
     */
    private array $parameterTypes = [];

    /**
     * @throws JsonMapperException
     */
    public function getParameterType(ReflectionParameter $parameter): UnionType
    {
        $cacheKey = $this->getCacheKey($parameter);

        if ($cacheKey !== null && isset($this->parameterTypes[$cacheKey])) {
            return $this->parameterTypes[$cacheKey];
        }

        $parameterType = $this->doGetParameterType($parameter);

        if ($cacheKey !== null) {
            $this->parameterTypes[$cacheKey] = $parameterType;
        }

        return $parameterType;
    }

    private function getCacheKey(ReflectionParameter $parameter): ?string
    {
        $function = $parameter->getDeclaringFunction();

        if ($function->isClosure()) {
            return null;
        }

        $class = $parameter->getDeclaringClass();

        $result = [];

        if ($class !== null) {
            $result[] = $class->getName();
        }

        $result[] = $function->getName();
        $result[] = $parameter->getName();

        return implode(':', $result);
    }

    /**
     * @throws JsonMapperException
     */
    private function doGetParameterType(ReflectionParameter $parameter): UnionType
    {
        $parameterType = $this->getParameterTypeFromDocComment($parameter);

        if ($parameterType !== null) {
            return $parameterType;
        }

        $parameterType = $parameter->getType();

        if ($parameterType === null) {
            throw new JsonMapperException([
                sprintf(
                    'Parameter %s must have a type or be documented with @param.',
                    $this->getParameterNameWithDeclaringFunctionName($parameter),
                ),
                'You can explicitly accept any type by typing the parameter as mixed.',
            ]);
        }

        return $this->getParameterTypeFromReflection($parameterType, $parameter);
    }

    /**
     * @throws JsonMapperException
     */
    private function getParameterTypeFromDocComment(ReflectionParameter $parameter): ?UnionType
    {
        $docComment = $parameter->getDeclaringFunction()->getDocComment();

        if ($docComment === false) {
            return null;
        }

        $pattern = '/@param(.*)\$' . $parameter->getName() . '\W/';

        /** @var list<array{string}> $matches */
        preg_match_all($pattern, $docComment, $matches, PREG_SET_ORDER);

        if (count($matches) === 0) {
            return null;
        }

        if (count($matches) !== 1) {
            throw new JsonMapperException(sprintf(
                'Parameter %s has multiple @param types.',
                $this->getParameterNameWithDeclaringFunctionName($parameter),
            ));
        }

        $type = $matches[0][1];

        if ($type === '' || ! $this->isWhitespace($type[0]) || ! $this->isWhitespace($type[-1])) {
            throw new JsonMapperException(sprintf(
                'Parameter %s has a badly-formatted @param type.',
                $this->getParameterNameWithDeclaringFunctionName($parameter),
            ));
        }

        $type = preg_replace('/(^\s+)|(\s+$)/', '', $type);

        if ($type === '') {
            throw new JsonMapperException(sprintf(
                'Parameter %s has an empty @param type.',
                $this->getParameterNameWithDeclaringFunctionName($parameter),
            ));
        }

        $typeParser = new TypeParser($type);

        try {
            $parsedType = $typeParser->parse();
        } catch (JsonMapperException $e) {
            throw new JsonMapperException(sprintf(
                'Parameter %s has an invalid @param type: %s',
                $this->getParameterNameWithDeclaringFunctionName($parameter),
                $e->getMessage(),
            ), previous: $e);
        }

        return $this->createUnionType(
            array_map(
                fn (string|array $value) => $this->convertDocCommentType($value, $parameter),
                $parsedType,
            ),
            $parameter,
        );
    }

    private function isWhitespace(string $char): bool
    {
        return preg_match('/\s/', $char) === 1;
    }

    /**
     * @throws JsonMapperException
     */
    private function convertDocCommentType(string|array $type, ReflectionParameter $reflectionParameter): SimpleType|ClassType|EnumType|ArrayType
    {
        if (is_string($type)) {
            return $this->convertNamedType($type, false, $reflectionParameter);
        }

        return new ArrayType(
            $this->createUnionType(
                array_map(
                    fn (string|array $type) => $this->convertDocCommentType($type, $reflectionParameter),
                    $type,
                ),
                $reflectionParameter,
            ),
        );
    }

    /**
     * @throws JsonMapperException
     */
    private function getParameterTypeFromReflection(ReflectionType $type, ReflectionParameter $reflectionParameter): UnionType
    {
        if ($type instanceof ReflectionIntersectionType) {
            $this->throwOnIntersectionType($reflectionParameter);
        }

        if ($type instanceof ReflectionUnionType) {
            return $this->createUnionType(
                array_map(
                    function (ReflectionNamedType|ReflectionIntersectionType $type) use ($reflectionParameter): SimpleType|ClassType|EnumType|ArrayType {
                        /** @psalm-suppress DocblockTypeContradiction https://github.com/vimeo/psalm/issues/9079 */
                        if ($type instanceof ReflectionIntersectionType) {
                            $this->throwOnIntersectionType($reflectionParameter);
                        }

                        return $this->convertNamedType($type->getName(), true, $reflectionParameter);
                    },
                    $type->getTypes(),
                ),
                $reflectionParameter,
            );
        }

        if ($type instanceof ReflectionNamedType) {
            $result = [
                 $this->convertNamedType($type->getName(), true, $reflectionParameter),
            ];

            if ($type->allowsNull() && $type->getName() !== 'mixed') {
                $result[] = new SimpleType('null');
            }

            return $this->createUnionType($result, $reflectionParameter);
        }

        // @codeCoverageIgnoreStart
        throw new LogicException(sprintf(
            'Unknown reflection type: %s',
            $type::class,
        ));
        // @codeCoverageIgnoreEnd
    }

    /**
     * @throws JsonMapperException
     */
    private function convertNamedType(string $namedType, bool $isReflection, ReflectionParameter $reflectionParameter): SimpleType|ClassType|EnumType
    {
        $namedTypeLower = strtolower($namedType);

        $isAllowedBuiltinType = in_array($namedTypeLower, self::ALLOWED_BUILTIN_TYPES, true);
        $isDisallowedBuiltinType = in_array($namedTypeLower, self::DISALLOWED_BUILTIN_TYPES, true);
        $isBuiltinType = $isAllowedBuiltinType || $isDisallowedBuiltinType;

        if (! $isBuiltinType && ! $isReflection) {
            // Class names coming from reflection are already fully qualified, while class names coming from @param
            // must be resolved according to the current namespace & use statements,
            $importResolver = new ImportResolver($reflectionParameter);

            $namedType = $importResolver->resolve($namedType);
            $namedTypeLower = strtolower($namedType);
        }

        if ($namedTypeLower === 'array' && ! $this->allowUntypedArrays) {
            throw new JsonMapperException([
                sprintf(
                    'Parameter %s contains type "array" which is not allowed by default.',
                    $this->getParameterNameWithDeclaringFunctionName($reflectionParameter),
                ),
                'Please document the type of the array in @param, for example "string[]".',
                'Alternatively, if you want to allow untyped arrays, and receive the raw JSON array, set $allowUntypedArrays to true.',
            ]);
        }

        if (($namedTypeLower === 'stdclass' || $namedTypeLower === 'object') && ! $this->allowUntypedObjects) {
            throw new JsonMapperException([
                sprintf(
                    'Parameter %s contains type "%s" which is not allowed by default.',
                    $this->getParameterNameWithDeclaringFunctionName($reflectionParameter),
                    $namedTypeLower === 'stdclass' ? stdClass::class : 'object',
                ),
                'It is advised to map a JSON object to a PHP class.',
                'If you want to allow this, and receive the raw stdClass object, set $allowUntypedObjects to true.',
            ]);
        }

        if ($namedTypeLower === 'mixed' && ! $this->allowMixed) {
            throw new JsonMapperException([
                sprintf(
                    'Parameter %s contains type "mixed" which is not allowed by default.',
                    $this->getParameterNameWithDeclaringFunctionName($reflectionParameter),
                ),
                'If you want to allow this, and receive the raw JSON value, set $allowMixed to true.'
            ]);
        }

        if ($namedTypeLower === 'stdclass') {
            return new SimpleType('object');
        }

        if ($isDisallowedBuiltinType) {
            throw new JsonMapperException(sprintf(
                'Parameter %s contains type "%s" which is not allowed.',
                $this->getParameterNameWithDeclaringFunctionName($reflectionParameter),
                $namedTypeLower,
            ));
        }

        if ($isAllowedBuiltinType) {
            return new SimpleType($namedTypeLower);
        }

        if (is_a($namedType, UnitEnum::class, true)) {
            $reflectionEnum = new ReflectionEnum($namedType);

            /** @var ReflectionNamedType|null $backingType */
            $backingType = $reflectionEnum->getBackingType();

            if ($backingType === null) {
                throw new JsonMapperException('Non-backed enums are not supported.');
            }

            $backingType = $backingType->getName();

            /** @var class-string<BackedEnum> $namedType */

            return new EnumType(
                $namedType,
                isIntBacked: $backingType === 'int',
                isStringBacked: $backingType === 'string',
            );
        }

        /** @psalm-var class-string $namedType */
        return new Type\ClassType($namedType);
    }

    /**
     * @throws JsonMapperException
     */
    private function throwOnIntersectionType(ReflectionParameter $parameter): never
    {
        throw new JsonMapperException(sprintf(
            'Parameter %s cannot have an intersection type.',
            $this->getParameterNameWithDeclaringFunctionName($parameter),
        ));
    }

    /**
     * @param (SimpleType|ClassType|EnumType|ArrayType)[] $types
     *
     * @throws JsonMapperException
     */
    private function createUnionType(array $types, ReflectionParameter $parameter): UnionType
    {
        try {
            return new UnionType($types);
        } catch (JsonMapperException $e) {
            throw new JsonMapperException(sprintf(
                'Parameter %s contains an invalid type: %s',
                $this->getParameterNameWithDeclaringFunctionName($parameter),
                $e->getMessage(),
            ), previous: $e);
        }
    }

    private function getParameterNameWithDeclaringFunctionName(ReflectionParameter $parameter): string
    {
        return sprintf(
            '$%s of %s',
            $parameter->getName(),
            $this->getDeclaringFunctionName($parameter),
        );
    }

    private function getDeclaringFunctionName(ReflectionParameter $parameter): string
    {
        $function = $parameter->getDeclaringFunction();
        $declaringClass = $parameter->getDeclaringClass();

        if ($function->isClosure() || $declaringClass === null) {
            return $function->getName();
        }

        return sprintf(
            '%s::%s()',
            $declaringClass->getName(),
            $function->getName(),
        );
    }
}
