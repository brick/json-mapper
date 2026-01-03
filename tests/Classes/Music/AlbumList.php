<?php

declare(strict_types=1);

namespace Brick\JsonMapper\Tests\Classes\Music;

final readonly class AlbumList
{
    /**
     * @param Album[] $albums
     */
    public function __construct(
        public array $albums,
    ) {
    }
}
