<?php

declare(strict_types=1);

namespace Brick\JsonMapper;

enum OnMissingProperties
{
    case ThrowException;
    case SetNull;
    case SetDefault;

    /**
     * @deprecated Use OnMissingProperties::ThrowException
     */
    public const THROW_EXCEPTION = self::ThrowException;

    /**
     * @deprecated Use OnMissingProperties::SetNull
     */
    public const SET_NULL = self::SetNull;

    /**
     * @deprecated Use OnMissingProperties::SetDefault
     */
    public const SET_DEFAULT = self::SetDefault;
}
