<?php

declare(strict_types=1);

namespace Brick\JsonMapper;

enum OnExtraProperties
{
    case ThrowException;
    case Ignore;

    /**
     * @deprecated Use OnExtraProperties::ThrowException
     */
    public const THROW_EXCEPTION = self::ThrowException;

    /**
     * @deprecated Use OnExtraProperties::Ignore
     */
    public const IGNORE = self::Ignore;
}
