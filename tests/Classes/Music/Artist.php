<?php

declare(strict_types=1);

namespace Brick\JsonMapper\Tests\Classes\Music;

final class Artist
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $picture,
    ) {
    }
}
