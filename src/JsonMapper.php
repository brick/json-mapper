<?php

declare(strict_types=1);

namespace Brick\JsonMapper;

use Brick\JsonMapper\NameMapper\NullMapper;
use Brick\JsonMapper\Reflection\Reflector;
use Brick\JsonMapper\Reflection\Type\UnionType;
use JsonException;
use LogicException;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;
use stdClass;

/**
 * @psalm-suppress MixedAssignment
 */
final class JsonMapper
{
    private readonly Reflector $reflector;

    public function __construct(
        /**
         * Allows untyped arrays, i.e. arrays without a corresponding "@param" docblock or with "@param array".
         * When a property declared as such is found, it will be assigned the raw JSON array.
         */
        bool $allowUntypedArrays = false,

        /**
         * Allows untyped objects, i.e. objects declared as "stdClass" or "object".
         * When a property declared as such is found, it will be assigned the raw JSON object.
         */
        bool $allowUntypedObjects = false,

        /**
         * Allows "mixed" type.
         * When a property declared as such is found, it will be assigned the raw JSON value.
         */
        bool $allowMixed = false,

        /**
         * Controls how extra properties in the JSON object are handled.
         * Extra properties are properties of the JSON object that do not have a corresponding constructor parameter in
         * the PHP class.
         */
        private readonly OnExtraProperties $onExtraProperties = OnExtraProperties::THROW_EXCEPTION,

        /**
         * Controls how missing properties in the JSON object are handled.
         * Missing properties are constructor parameters in the PHP class that do not have a corresponding property in
         * the JSON object.
         */
        private readonly OnMissingProperties $onMissingProperties = OnMissingProperties::THROW_EXCEPTION,

        /**
         * Mapper to convert JSON property names to PHP property names.
         * By default, no conversion is performed.
         */
        private readonly NameMapper $jsonToPhpNameMapper = new NullMapper(),

        /**
         * Mapper to convert PHP property names to JSON property names.
         * By default, no conversion is performed.
         */
        private readonly NameMapper $phpToJsonNameMapper = new NullMapper(),
    ) {
        $this->reflector = new Reflector(
            $allowUntypedArrays,
            $allowUntypedObjects,
            $allowMixed,
        );
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $className
     *
     * @return T
     *
     * @throws JsonMapperException
     */
    public function map(string $json, string $className): object
    {
        try {
            $data = json_decode($json, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new JsonMapperException('Invalid JSON data: ' . $e->getMessage(), $e);
        }

        if (! $data instanceof stdClass) {
            throw new JsonMapperException(sprintf('Unexpected JSON data: expected object, got %s.', gettype($data)));
        }

        return $this->mapToObject($data, $className);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $className
     *
     * @return T
     *
     * @throws JsonMapperException
     */
    private function mapToObject(stdClass $jsonData, string $className): object
    {
        try {
            $reflectionClass = new ReflectionClass($className);
        } catch (ReflectionException $e) {
            throw new JsonMapperException('Invalid class name: ' . $className, $e);
        }

        $reflectionConstructor = $reflectionClass->getConstructor();

        if ($reflectionConstructor === null) {
            throw new JsonMapperException('Class ' . $className . ' must have a constructor.');
        }

        $parameters = [];

        $consumedJsonPropertyNames = [];

        foreach ($reflectionConstructor->getParameters() as $reflectionParameter) {
            $jsonPropertyName = $this->phpToJsonNameMapper->mapName($reflectionParameter->getName());
            $consumedJsonPropertyNames[] = $jsonPropertyName;

            $parameters[$reflectionParameter->getName()] = $this->getParameterValue(
                $jsonData,
                $jsonPropertyName,
                $reflectionParameter,
            );
        }

        if ($this->onExtraProperties === OnExtraProperties::THROW_EXCEPTION) {
            /** @psalm-suppress MixedAssignment, RawObjectIteration */
            foreach ($jsonData as $jsonPropertyName => $_) {
                /** @var string $jsonPropertyName https://github.com/vimeo/psalm/issues/9108 */
                if (! in_array($jsonPropertyName, $consumedJsonPropertyNames, true)) {
                    throw new JsonMapperException([
                        sprintf(
                            'Unexpected property "%s" in JSON data: ' .
                            '%s::__construct() does not have a corresponding $%s parameter.',
                            $jsonPropertyName,
                            $className,
                            $this->jsonToPhpNameMapper->mapName($jsonPropertyName),
                        ),
                        'If you want to allow extra properties, change the $onExtraProperties option.',
                    ]);
                }
            }
        }

        return $reflectionClass->newInstanceArgs($parameters);
    }

    /**
     * @throws JsonMapperException
     */
    private function getParameterValue(
        stdClass $jsonData,
        string $jsonPropertyName,
        ReflectionParameter $reflectionParameter,
    ): mixed {
        $parameterType = $this->reflector->getParameterType($reflectionParameter);

        if (!property_exists($jsonData, $jsonPropertyName)) {
            if ($this->onMissingProperties === OnMissingProperties::SET_NULL) {
                if ($parameterType->allowsNull) {
                    return null;
                }
            }

            if ($this->onMissingProperties === OnMissingProperties::SET_DEFAULT) {
                if ($reflectionParameter->isDefaultValueAvailable()) {
                    // TODO we should technically check if the default value is compatible with the parameter type,
                    //      as the type declared as @param may be more specific than the PHP type.
                    return $reflectionParameter->getDefaultValue();
                }
            }

            throw new JsonMapperException([
                sprintf('Missing property "%s" in JSON data.', $jsonPropertyName),
                match ($this->onMissingProperties) {
                    OnMissingProperties::SET_NULL => 'The parameter does not allow null.',
                    OnMissingProperties::SET_DEFAULT => 'The parameter does not have a default value.',
                    OnMissingProperties::THROW_EXCEPTION => 'If you want to allow missing properties, change the $onMissingProperties option.',
                }
            ]);
        }

        $jsonValue = $jsonData->{$jsonPropertyName};

        return $this->mapValue($jsonValue, $jsonPropertyName, $parameterType);
    }

    /**
     * @throws JsonMapperException
     */
    private function mapValue(
        mixed $jsonValue,
        string $jsonPropertyName,
        UnionType $parameterType,
    ): mixed {
        if ($parameterType->allowsMixed) {
            return $jsonValue;
        }

        if ($jsonValue instanceof stdClass) {
            return $this->getJsonObjectValue($jsonValue, $jsonPropertyName, $parameterType);
        }

        if (is_array($jsonValue)) {
            if ($parameterType->allowsRawArray) {
                return $jsonValue;
            }

            if ($parameterType->arrayType === null) {
                throw new JsonMapperException('Property ' . $jsonPropertyName . ' is an array, but the parameter does not accept arrays.');
            }

            $type = $parameterType->arrayType->type;

            return array_map(
                // TODO $jsonPropertyName is wrong here, rework the exception message
                fn (mixed $item): mixed => $this->mapValue($item, $jsonPropertyName, $type),
                $jsonValue,
            );
        }

        if (is_string($jsonValue)) {
            if ($parameterType->allowsString) {
                return $jsonValue;
            }

            foreach ($parameterType->enumTypes as $enumType) {
                if ($enumType->isStringBacked) {
                    return ($enumType->name)::from($jsonValue);
                }
            }

            // TODO "Parameter %s of class %s does not accept string" + JSON path
            throw new JsonMapperException('Property ' . $jsonPropertyName . ' cannot be a string.');
        }

        if (is_int($jsonValue)) {
            if ($parameterType->allowsInt) {
                return $jsonValue;
            }

            foreach ($parameterType->enumTypes as $enumType) {
                if ($enumType->isIntBacked) {
                    return ($enumType->name)::from($jsonValue);
                }
            }

            throw new JsonMapperException('Property ' . $jsonPropertyName . ' cannot be a string.');
        }

        if (is_float($jsonValue)) {
            if ($parameterType->allowsFloat) {
                return $jsonValue;
            }

            throw new JsonMapperException('Property ' . $jsonPropertyName . ' cannot be a float.');
        }

        if ($jsonValue === null) {
            if ($parameterType->allowsNull) {
                return null;
            }

            throw new JsonMapperException('Property ' . $jsonPropertyName . ' cannot be null.');
        }

        if ($jsonValue === true) {
            if ($parameterType->allowsTrue) {
                return true;
            }

            throw new JsonMapperException('Property ' . $jsonPropertyName . ' cannot be true.');
        }

        if ($jsonValue === false) {
            if ($parameterType->allowsFalse) {
                return false;
            }

            throw new JsonMapperException('Property ' . $jsonPropertyName . ' cannot be false.');
        }

        throw new LogicException('Unreachable. If you see this, please report a bug.');
    }

    /**
     * @throws JsonMapperException
     */
    private function getJsonObjectValue(
        stdClass $jsonValue,
        string $jsonPropertyName,
        UnionType $parameterType,
    ): object {
        if ($parameterType->allowsRawObject) {
            return $jsonValue;
        }

        if (! $parameterType->classTypes) {
            throw new JsonMapperException('Property ' . $jsonPropertyName . ' is an object, but the parameter does not accept objects.');
        }

        if (count($parameterType->classTypes) === 1) {
            return $this->mapToObject($jsonValue, $parameterType->classTypes[0]->name);
        }

        $matches = [];
        $errors = [];

        foreach ($parameterType->classTypes as $classType) {
            try {
                $matches[] = $this->mapToObject($jsonValue, $classType->name);
            } catch (JsonMapperException $e) {
                $errors[] = [$classType->name, $e->getFirstMessage()];
            }
        }

        if (! $matches) {
            throw new JsonMapperException(
                "JSON object does not match any of the allowed PHP classes:\n" . implode("\n", array_map(
                    fn (array $error) => sprintf(' - %s: %s', ...$error),
                    $errors,
                ),
            ));
        }

        if (count($matches) === 1) {
            return $matches[0];
        }

        throw new JsonMapperException(sprintf(
            'JSON object matches multiple PHP classes: %s.',
            implode(', ', array_map(get_class(...), $matches)),
        ));
    }
}
