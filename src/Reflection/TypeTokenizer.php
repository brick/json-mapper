<?php

declare(strict_types=1);

namespace Brick\JsonMapper\Reflection;

use Brick\JsonMapper\JsonMapperException;

/**
 * Splits a string into individual tokens.
 *
 * Valid tokens:
 *  - named type: [A-Z] [a-z] "_" "\"
 *  - "|",
 *  - "("
 *  - ")"
 *  - "[]"
 *
 * Whitespace around tokens is ignored.
 *
 * @internal This class is not part of the public API, and may change without notice.
 */
final class TypeTokenizer
{
    private const PATTERN = '/'
        . '(?<namedType>[' . ('A-Z' . 'a-z' . '_' . '\\\\') . ']+)'
        . '|'
        . '(?<symbol>[' . ('\|' . '\(' . '\)') . ']' . '|' . '\[\]' . ')'
        . '|'
        . '(?<whitespace>\s+)'
        . '|'
        . '(?<other>[^' . ('A-Z' . 'a-z' . '_' . '\\\\' . '\|' . '\(' . '\)' . '\s') . ']+' . '|' . '(?<!\[)\]' . '|' . '\[(?!\])' . ')'
        . '/';

    /**
     * @return TypeToken[]
     *
     * @psalm-return list<TypeToken>
     *
     * @throws JsonMapperException
     */
    public static function tokenize(string $type): array
    {
        /** @var list<array<int|string, array{string, int}>> $matches */
        preg_match_all(self::PATTERN, $type, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        $tokens = [];

        foreach ($matches as $match) {
            foreach ($match as $group => [$value, $offset]) {
                if (is_int($group)) {
                    continue;
                }

                if ($value === '') {
                    continue;
                }

                if ($group === 'whitespace') {
                    continue 2;
                }

                if ($group === 'other') {
                    throw new JsonMapperException(match ($value) {
                        '[' => sprintf('Char "[" is not followed by "]" at offset %d.', $offset),
                        ']' => sprintf('Char "]" is not preceded by "[" at offset %d.', $offset),
                        default => sprintf('Unexpected "%s" at offset %d.',  $value, $offset),
                    });
                }

                $tokens[] = new TypeToken($value, $offset, match ($group) {
                    'namedType' => true,
                    'symbol' => false,
                });
            }
        }

        return $tokens;
    }
}
