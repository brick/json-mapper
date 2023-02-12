# Brick\JsonMapper

<img src="https://raw.githubusercontent.com/brick/brick/master/logo.png" alt="" align="left" height="64">

Maps JSON data to strongly typed PHP DTOs.

[![Build Status](https://github.com/brick/json-mapper/workflows/CI/badge.svg)](https://github.com/brick/json-mapper/actions)
[![Coverage Status](https://coveralls.io/repos/github/brick/json-mapper/badge.svg?branch=master)](https://coveralls.io/github/brick/json-mapper?branch=master)
[![Latest Stable Version](https://poser.pugx.org/brick/json-mapper/v/stable)](https://packagist.org/packages/brick/json-mapper)
[![Total Downloads](https://poser.pugx.org/brick/json-mapper/downloads)](https://packagist.org/packages/brick/json-mapper)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](http://opensource.org/licenses/MIT)

## Introduction

This library provides an easy-to-use, secure, and powerful way to map JSON data to strongly typed PHP objects.

It reads parameter types & annotations defined on your class constructors to map JSON data to your DTOs, and can work with zero configuration.

### Installation

This library is installable via [Composer](https://getcomposer.org/):

```bash
composer require brick/json-mapper
```

### Requirements

This library requires PHP 8.1 or later.

### Project status & release process

While this library is still under development, it is well tested and considered stable enough to use in production environments.

The current releases are numbered `0.x.y`. When a non-breaking change is introduced (adding new methods, optimizing existing code, etc.), `y` is incremented.

**When a breaking change is introduced, a new `0.x` version cycle is always started.**

It is therefore safe to lock your project to a given release cycle, such as `0.1.*`.

If you need to upgrade to a newer release cycle, check the [release history](https://github.com/brick/json-mapper/releases) for a list of changes introduced by each further `0.x.0` version.

## Usage

### Basic usage

`JsonMapper` provides a single method, `map()`, which takes a JSON string and a class name, and returns an instance of the given class.

```php
use Brick\JsonMapper\JsonMapper;

class User
{
    public function __construct(
        public int $id,
        public string $name,
    ) {
    }
}

$json = '{
  "id": 123,
  "name": "John Doe"
}';

$mapper = new JsonMapper();
$user = $mapper->map($json, User::class);

echo $user->name; // John Doe
```

### Nested objects

`JsonMapper` will read the parameter types and annotations to map nested objects:

```php
class Album
{
    public function __construct(
        public int $id,
        public string $title,
        public Artist $artist,
    ) {
    }
}

class Artist
{
    public function __construct(
        public int $id,
        public string $name,
    ) {
    }
}

$json = '{
  "id": 456,
  "title": "The Wall",
  "artist": {
    "id": 789,
    "name": "Pink Floyd"
  }
}';

$mapper = new JsonMapper();
$album = $mapper->map($json, Album::class);

echo $album->artist->name; // Pink Floyd
```

### Arrays

Arrays can be documented with `@param` annotations, that will be parsed and used to map the JSON data:

```php
class Customer
{
    /**
     * @param Address[] $addresses
     */
    public function __construct(
        public int $id,
        public string $name,
        public array $addresses,
    ) {
    }
}

class Address
{
    public function __construct(
        public string $street,
        public string $city,
    ) {
    }
}

$json = '{
  "id": 123,
  "name": "John Doe",
  "addresses": [
    {
      "street": "123 Main Street",
      "city": "New York"
    },
    {
      "street": "456 Side Street",
      "city": "New York"
    }
  ]
}';

$mapper = new JsonMapper();
$customer = $mapper->map($json, Customer::class);

foreach ($customer->addresses as $address) {
    var_export($address instanceof Address); // true
}
```

### Union types

If a parameter is a declared as a union of possible types, `JsonMapper` will automatically attempt to map the JSON data to the correct type:

```php
class Order
{
    public function __construct(
        public readonly int $id,
        public readonly string $amount,
        public readonly Person|Company $customer, // union type
    ) {
    }
}

class Person
{
    public function __construct(
        public readonly int $id,
        public readonly string $firstname,
        public readonly string $lastname,
    ) {
    }
}

class Company
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $companyNumber,
    ) {
    }
}

$json = '{
  "id": 1,
  "amount": "24.99",
  "customer": {
    "id": 2,
    "firstname": "John",
    "lastname": "Doe"
  }
}';

$mapper = new JsonMapper();
$order = $mapper->map($json, Order::class);

// JsonMapper automatically determined that the "id", "firstname",
// and "lastname" properties correspond to a Person and not a Company.
var_export($order->customer instanceof Person); // true
```

To achieve this, `JsonMapper` attempts to map a JSON object to every possible PHP class in the union. If no class matches, or if several classes match, an exception is thrown.

### Complex unions

`JsonMapper` can **parse, map, and verify** any combination of possibly nested types:

```php
/**
 * @param (Person|Company|(string|int)[])[]|null $customers
 */
public function __construct(
    public readonly ?array $customers,
) {
}
```

This currently comes with two limitations:

- you must use the `Type[]` syntax for arrays, and not the `array<Type>` syntax;
- you cannot use more than one array type per union; for example, this is allowed:

    ```php
    /**
     * @param (Person|Company)[] $value
     */
    ```
    
    but this is not:
    
    ```php
    /**
     * @param Person[]|Company[] $value
     */
    ```

### Enums

`JsonMapper` can map JSON strings and integers to backed enums:

```php
class Order
{
    public function __construct(
        public readonly int $id,
        public readonly OrderStatus $status,
    ) {
    }
}

enum OrderStatus: string {
    case PENDING = 'pending';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
}

$json = '{
  "id": 1,
  "status": "shipped"
}';

$mapper = new JsonMapper();
$order = $mapper->map($json, Order::class);

var_export($order->status === OrderStatus::SHIPPED); // true
```

Non-backed enums, i.e. enums that do not have a `string` or `int` value, are not supported on purpose.

### Strictness

The library has very strict defaults (some of which can be overridden by config), and will throw an exception if the JSON data does not exactly match the DTO's constructor signature, or if the DTO contains invalid or unsupported `@param` annotations.

The types of the parameters must match exactly, with the same semantics as PHP's `strict_types`.

**`JsonMapper` guarantees that every constructor parameter, even softly-typed with `@param`, will be passed a value that is compatible with the declared type.** The result is a DTO that can be 100% trusted by your static analysis tool.

### Options

The `JsonMapper` constructor accepts the following options:

---

#### `$allowUntypedArrays`

By default, `JsonMapper` will throw an exception if the parameter is declared as `array` without a corresponding `@param` annotation, or is just documented as `@param array`.

By setting this option to `true`, `JsonMapper` will allow such parameters, and accept to pass a JSON array as is, without checking or mapping its contents:

```php
$mapper = new JsonMapper(
    allowUntypedArrays: true,
);
```

---

#### `$allowUntypedObjects`

By default, `JsonMapper` will throw an exception if a parameter is declared as `object` or `stdClass`.

By setting this option to `true`, `JsonMapper` will allow such parameters, and accept to pass a JSON object as an `stdClass` instance, without checking or mapping its contents:

```php
$mapper = new JsonMapper(
    allowUntypedObjects: true,
);
```

---

#### `$allowMixed`

By default, `JsonMapper` will throw an exception if a parameter is declared as `mixed`.

By setting this option to `true`, `JsonMapper` will allow such parameters, and accept to pass a JSON value as is, without checking or mapping its contents:

```php
$mapper = new JsonMapper(
    allowMixed: true,
);
```

---

#### `$allowExtraProperties`

By default, `JsonMapper` will throw an exception if a JSON object contains a property that does not have a matching parameter in the corresponding DTO's constructor signature.

By setting this option to `true`, `JsonMapper` will ignore these extra properties:

```php
class Order
{
    public function __construct(
        public readonly int $id,
        public readonly string $amount,
    ) {
    }
}

$json = '{
  "id": 1,
  "amount": "100.00",
  "extraProperty": "foo",
  "otherExtraProperty": "bar"
}';

$mapper = new JsonMapper(
    allowExtraProperties: true,
);

// extra properties "extraProperty" and "otherExtraProperty" are ignored,
// and do not throw an exception anymore.
$order = $mapper->map($json, Order::class);
```

---

#### `$allowMissingPropertiesSetNull`

By default, `JsonMapper` will throw an exception if a JSON object does not contain a property that is declared in the corresponding DTO's constructor signature.

By setting this option to `true`, `JsonMapper` will set the parameter to `null` if the parameter is nullable:

```php
class Order
{
    public function __construct(
        public readonly int $id,
        public readonly string $amount,
        public readonly ?string $customerName,
    ) {
    }
}

$json = '{
  "id": 1,
  "amount": "100.00"
}';

$mapper = new JsonMapper(
    allowMissingPropertiesSetNull: true,
);

$order = $mapper->map($json, Order::class);
var_export($order->customerName); // NULL
```

If the property is missing and the parameter is not nullable, an exception will be thrown regardless of this option.

---

#### `$jsonToPhpNameMapper` & `$phpToJsonNameMapper`

By default, `JsonMapper` assumes that the JSON property names are the same as the PHP parameter names.

By providing implementations of the `NameMapper` interface, you can customize the mapping between the two.

The library comes with two implementations for a common use case:

- `SnakeCaseToCamelCaseMapper` will convert `snake_case` `camelCase`
- `CamelCaseToSnakeCaseMapper` will convert `camelCase` to `snake_case`

Example:

```php
use Brick\JsonMapper\JsonMapper;
use Brick\JsonMapper\NameMapper\CamelCaseToSnakeCaseMapper;
use Brick\JsonMapper\NameMapper\SnakeCaseToCamelCaseMapper;

class Order
{
    public function __construct(
        public readonly int $id,
        public readonly int $amountInCents,
        public readonly string $customerName,
    ) {
    }
}

$json = '{
  "id": 1,
  "amount_in_cents": 2499,
  "customer_name": "John Doe"
}';

$mapper = new JsonMapper(
    jsonToPhpNameMapper: new SnakeCaseToCamelCaseMapper(),
    phpToJsonNameMapper: new CamelCaseToSnakeCaseMapper(),
);

$order = $mapper->map($json, Order::class);
echo $order->amountInCents; // 2499
```
