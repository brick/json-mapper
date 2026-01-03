<?php

declare(strict_types=1);

namespace Brick\JsonMapper\Tests\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
final readonly class ExpectParameterType
{
    /**
     * @param array{
     *     allowUntypedArrays?: true,
     *     allowUntypedObjects?: true,
     *     allowMixed?: true,
     * } $config
     */
    public function __construct(
        public string $type,
        public array $config = [],
    ) {
    }
}
