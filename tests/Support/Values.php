<?php

declare(strict_types=1);

namespace PhpChess\Tests\Support;

use PHPUnit\Framework\Assert;

/**
 * Typed values for tests: decoded JSON and database answers arrive as
 * "mixed"; these helpers check the type and fail the test when it is
 * wrong, so the tests read plainly and PHPStan knows the types.
 */
final class Values
{
    /**
     * A JSON object as an array.
     *
     * @return array<string, mixed>
     */
    public static function jsonObject(string $json): array
    {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            Assert::fail('Not a JSON object: ' . substr($json, 0, 200));
        }

        /** @var array<string, mixed> $data */
        return $data;
    }

    /**
     * A list of rows (arrays) from decoded JSON.
     *
     * @return list<array<string, mixed>>
     */
    public static function rows(mixed $value): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            Assert::fail('Expected a list of rows.');
        }
        $rows = [];
        foreach ($value as $row) {
            if (!is_array($row)) {
                Assert::fail('Expected every entry to be a row.');
            }
            /** @var array<string, mixed> $row */
            $rows[] = $row;
        }

        return $rows;
    }

    public static function int(mixed $value): int
    {
        if (!is_numeric($value)) {
            Assert::fail('Expected a number, got ' . get_debug_type($value) . '.');
        }

        return (int) $value;
    }

    public static function string(mixed $value): string
    {
        if (!is_scalar($value)) {
            Assert::fail('Expected text, got ' . get_debug_type($value) . '.');
        }

        return (string) $value;
    }

    /** The first group of a regular expression in a text; fails the test when it isn't there. */
    public static function firstMatch(string $pattern, string $text): string
    {
        if (preg_match($pattern, $text, $found) !== 1) {
            Assert::fail("Pattern {$pattern} not found.");
        }

        return $found[1] ?? '';
    }
}
