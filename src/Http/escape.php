<?php

/**
 * The escape function e(), used for every value printed in a template (contract K6).
 *
 * Loaded by Composer's autoloader ("files" in composer.json), so it exists
 * everywhere. It turns <, >, &, " and ' into HTML entities, so a value can
 * never become HTML or script.
 */

declare(strict_types=1);

if (!function_exists('e')) {
    /** Escapes a value for HTML text and attribute values. */
    function e(string|int|float|null $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
