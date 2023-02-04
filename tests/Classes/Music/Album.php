<?php

declare(strict_types=1);

namespace Brick\JsonMapper\Tests\Classes\Music;

final class Album
{
    /**
     * @param Artist[] $contributors
     */
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly Artist $artist,
        public readonly array $contributors,
        public readonly ?string $picture,
    ) {
    }
}
