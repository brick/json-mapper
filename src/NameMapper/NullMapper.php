<?php

declare(strict_types=1);

namespace Brick\JsonMapper\NameMapper;

use Brick\JsonMapper\NameMapper;
use Override;

final class NullMapper implements NameMapper
{
    #[Override]
    public function mapName(string $name): string
    {
        return $name;
    }
}
