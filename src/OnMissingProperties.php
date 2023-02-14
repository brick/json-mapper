<?php

declare(strict_types=1);

namespace Brick\JsonMapper;

enum OnMissingProperties
{
    case THROW_EXCEPTION;
    case SET_NULL;
}
