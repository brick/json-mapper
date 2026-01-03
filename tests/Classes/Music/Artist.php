<?php

declare(strict_types=1);

namespace Brick\JsonMapper\Tests\Classes\Music;

final readonly class Artist
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $picture = 'default-picture', // allows for testing of OnMissingProperties::SET_NULL & SET_DEFAULT
    ) {
    }
}
