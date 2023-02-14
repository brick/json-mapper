<?php

declare(strict_types=1);

namespace Brick\JsonMapper;

enum OnExtraProperties
{
    case THROW_EXCEPTION;
    case IGNORE;
}
