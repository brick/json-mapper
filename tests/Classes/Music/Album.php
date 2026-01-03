<?php

declare(strict_types=1);

namespace Brick\JsonMapper\Tests\Classes\Music;

final readonly class Album
{
    /**
     * @param Artist[] $contributors
     */
    public function __construct(
        public int $id,
        public string $title,
        public Artist $artist,
        public array $contributors,
        public ?string $picture,
    ) {
    }
}
