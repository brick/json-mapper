<?php

declare(strict_types=1);

namespace Brick\JsonMapper\Tests\Reflection;

use Brick\JsonMapper\JsonMapperException;
use Brick\JsonMapper\Reflection\Reflector;
use Brick\JsonMapper\Tests\Attributes\ExpectException;
use Brick\JsonMapper\Tests\Attributes\ExpectParameterType;
use Brick\JsonMapper\Tests\Classes\KitchenSink;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionParameter;

use function array_map;
use function array_merge;
use function sprintf;

/**
 * @psalm-type Config = array{
 *     allowUntypedArrays?: true,
 *     allowUntypedObjects?: true,
 *     allowMixed?: true,
 * }
 */
final class ReflectorTest extends TestCase
{
    /**
     * @param Config $config
     */
    #[DataProvider('providerGetParameterType')]
    public function testGetParameterType(ReflectionParameter $parameter, string $expectedType, array $config): void
    {
        $reflector = new Reflector(...$config);
        $actualType = $reflector->getParameterType($parameter);

        self::assertSame($expectedType, (string) $actualType);

        // test cache
        self::assertSame($actualType, $reflector->getParameterType($parameter));
    }

    /**
     * @return Generator<string, array{ReflectionParameter, string, Config}>
     */
    public static function providerGetParameterType(): Generator
    {
        foreach (self::getKitchenSinkMethodParameters() as $parameterIdentifier => $parameter) {
            $attributes = self::getAttributes($parameter, ExpectParameterType::class);

            foreach ($attributes as $attribute) {
                yield $parameterIdentifier => [$parameter, $attribute->type, $attribute->config];
            }
        }
    }

    /**
     * @param Config $config
     */
    #[DataProvider('providerGetParameterTypeThrowsException')]
    public function testGetParameterTypeThrowsException(ReflectionParameter $parameter, string $exceptionMessage, array $config): void
    {
        $reflector = new Reflector(...$config);

        $this->expectException(JsonMapperException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $reflector->getParameterType($parameter);
    }

    /**
     * @return Generator<string, array{ReflectionParameter, string, Config}>
     */
    public static function providerGetParameterTypeThrowsException(): Generator
    {
        foreach (self::getKitchenSinkMethodParameters() as $parameterIdentifier => $parameter) {
            $attributes = self::getAttributes($parameter, ExpectException::class);

            foreach ($attributes as $attribute) {
                yield $parameterIdentifier => [$parameter, $attribute->message, $attribute->config];
            }
        }
    }

    #[DataProvider('providerKitchenSinkMethodsHaveExpectations')]
    public function testKitchenSinkMethodsHaveExpectations(ReflectionParameter $parameter): void
    {
        $attributes = array_merge(
            self::getAttributes($parameter, ExpectException::class),
            self::getAttributes($parameter, ExpectParameterType::class),
        );

        self::assertNotEmpty($attributes, 'Parameter has no expectations.');
    }

    public static function providerKitchenSinkMethodsHaveExpectations(): Generator
    {
        foreach (self::getKitchenSinkMethodParameters() as $parameterIdentifier => $parameter) {
            yield $parameterIdentifier => [$parameter];
        }
    }

    /**
     * @return Generator<string, ReflectionParameter>
     */
    private static function getKitchenSinkMethodParameters(): Generator
    {
        $class = new ReflectionClass(KitchenSink::class);

        foreach ($class->getMethods() as $method) {
            foreach ($method->getParameters() as $parameter) {
                $parameterIdentifier = sprintf(
                    '%s($%s)',
                    $parameter->getDeclaringFunction()->getName(),
                    $parameter->getName(),
                );

                yield $parameterIdentifier => $parameter;
            }
        }
    }

    /**
     * @template T
     *
     * @param class-string<T> $className
     *
     * @return T[]
     */
    private static function getAttributes(ReflectionParameter $parameter, string $className): array
    {
        return array_map(
            fn (ReflectionAttribute $attribute) => $attribute->newInstance(),
            $parameter->getAttributes($className),
        );
    }
}
