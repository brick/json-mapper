<?php

declare(strict_types=1);

namespace Brick\JsonMapper;

interface NameMapper
{
    public function mapName(string $name): string;
}
