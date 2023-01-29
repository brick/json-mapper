<?php

declare(strict_types=1);

namespace Brick\JsonMapper\Tests\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
final class ExpectParameterType
{
    /**
     * @param array{
     *     allowUntypedArrays?: true,
     *     allowUntypedObjects?: true,
     *     allowMixed?: true,
     * } $config
     */
    public function __construct(
        public readonly string $type,
        public readonly array $config = [],
    ) {
    }
}
