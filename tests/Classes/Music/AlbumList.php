<?php

declare(strict_types=1);

namespace Brick\JsonMapper\Tests\Classes\Music;

final class AlbumList
{
    /**
     * @param Album[] $albums
     */
    public function __construct(
        public readonly array $albums,
    ) {
    }
}
