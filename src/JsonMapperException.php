<?php

declare(strict_types=1);

namespace Brick\JsonMapper;

use Exception;

final class JsonMapperException extends Exception
{
    /**
     * @var non-empty-list<string>
     */
    private array $messages;

    /**
     * @param string|non-empty-list<string> $messages
     */
    public function __construct(string|array $messages, ?Exception $previous = null)
    {
        if (is_string($messages)) {
            $messages = [$messages];
        }

        parent::__construct(implode(' ', $messages), 0, $previous);

        $this->messages = $messages;
    }

    public function getFirstMessage(): string
    {
        return $this->messages[0];
    }
}
