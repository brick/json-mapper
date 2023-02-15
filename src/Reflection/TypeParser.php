<?php

declare(strict_types=1);

namespace Brick\JsonMapper\Reflection;

use Brick\JsonMapper\JsonMapperException;

/**
 * Parses the given string and returns a nested representation of the types.
 *
 * Examples:
 *
 * - 'string' => ['string']
 * - 'string|null' => ['string', 'null']
 * - 'int[]|null' => [['int'], 'null']
 * - (string|int)[] => [['string', 'int']]
 * - (string|int)[]|null => [['string', 'int'], 'null']
 *
 * Disallowed:
 *
 * - nullable types: '?string'
 * - generics style: 'array<string>'
 * - intersection types: 'A&B'
 *
 * @internal This class is not part of the public API, and may change without notice.
 */
final class TypeParser
{
    /**
     * @var TypeToken[]
     *
     * @psalm-var list<TypeToken>
     */
    private array $tokens = [];

    private int $pointer = 0;

    public function __construct(
        private string $type,
    ) {
    }

    /**
     * @throws JsonMapperException
     */
    public function parse(): array
    {
        $this->tokens = TypeTokenizer::tokenize($this->type);
        $this->pointer = 0;

        $result = $this->parseUnion(false);

        $nextToken = $this->nextToken();

        if ($nextToken !== null) {
            $this->failExpectation(['end of string', '|', '[]'], $nextToken);
        }

        return $result;
    }

    /**
     * @throws JsonMapperException
     */
    private function parseUnion(bool $nested): array
    {
        $values = [];

        for (;;) {
            $value = $this->parseValue();

            if ($this->isNextToken('[]')) {
                if (is_array($value)) {
                    $this->advance();
                }

                while ($this->isNextToken('[]')) {
                    $this->advance();
                    $value = [$value];
                }

                $values[] = $value;
            } elseif (is_array($value)) {
                // unnecessary parentheses, merge into current values
                $values = array_merge($values, $value);
            } else {
                $values[] = $value;
            }

            $peekToken = $this->peekToken();

            if ($peekToken === null || $peekToken->value === ')') {
                break;
            }

            if ($peekToken->value === '|') {
                $this->advance();
                continue;
            }

            $this->failExpectation(
                array_merge(['|', '[]'], $nested ? [')'] : []),
                $peekToken,
            );
        }

        return $values;
    }

    /**
     * @throws JsonMapperException
     */
    private function parseValue(): string|array
    {
        $nextToken = $this->nextToken();

        if ($nextToken !== null) {
            if ($nextToken->isNamedType) {
                return $nextToken->value;
            }

            if ($nextToken->value === '(') {
                $value = $this->parseUnion(true);

                $nextToken = $this->nextToken();

                if ($nextToken === null || $nextToken->value !== ')') {
                    $this->failExpectation([')', '|', '[]'], $nextToken);
                }

                return $value;
            }
        }

        $this->failExpectation(['named type', '('], $nextToken);
    }

    /**
     * Retrieves the next token, and advances the pointer.
     */
    private function nextToken(): ?TypeToken
    {
        if ($this->pointer === count($this->tokens)) {
            return null;
        }

        return $this->tokens[$this->pointer++];
    }

    /**
     * Peeks at the next token, without advancing the pointer.
     */
    private function peekToken(): ?TypeToken
    {
        if ($this->pointer === count($this->tokens)) {
            return null;
        }

        return $this->tokens[$this->pointer];
    }

    /**
     * Advances the pointer.
     */
    private function advance(): void
    {
        $this->pointer++;
    }

    /**
     * Checks if the next token, if any, matches the given value.
     * Does not advance the pointer.
     */
    private function isNextToken(string $value): bool
    {
        $nextToken = $this->peekToken();

        return $nextToken !== null && $nextToken->value === $value;
    }

    /**
     * @param string[] $expected
     *
     * @throws JsonMapperException
     */
    private function failExpectation(array $expected, ?TypeToken $actual): never
    {
        $expected = implode(' or ' , array_map(
            fn (string $value) => in_array($value, ['(', ')', '|', '[]'], true) ? "\"$value\"" : $value,
            $expected,
        ));

        $actual = ($actual === null)
            ? 'end of string'
            : sprintf('"%s" at offset %d', $actual->value, $actual->offset);

        throw new JsonMapperException(sprintf('Expected %s, found %s.', $expected, $actual));
    }
}
