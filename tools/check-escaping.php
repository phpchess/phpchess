<?php

/**
 * Escaping check (contract K6, ARCHITECTURE.md §4).
 *
 * Every value printed in a template must go through e(), so it can never
 * become HTML or script. This script fails (exit code 1) when a "<?=" tag in
 * templates/ prints anything else. Allowed are only:
 *
 *   <?= e(...) ?>                     an escaped value
 *   <?= $template->render(...) ?>     a part, rendered (and escaped) by its own template
 *   <?= $somethingHtml ?>             a variable whose name ends in "Html": HTML made by a template
 *   <?= $x ? ' selected' : '' ?>      a choice between fixed texts written in the template
 *
 * "echo" and "print" are not allowed in templates at all.
 *
 * Run from the project root:  php tools/check-escaping.php
 */

declare(strict_types=1);

// --- Finding the templates ---

/**
 * @return list<string>
 */
function listTemplates(string $folder): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file instanceof SplFileInfo && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }
    sort($files);

    return $files;
}

// --- Checking one printed expression ---

/** True when the expression inside "<?= ... ?>" is one of the allowed forms. */
function isAllowedOutput(string $expression): bool
{
    $expression = trim((string) preg_replace('#/\*.*?\*/#s', '', $expression));

    if (str_starts_with($expression, 'e(') || str_starts_with($expression, '$template->render(')) {
        // The whole expression must be this one call: "e($a) . $b" would print $b unescaped.
        return isOneCall($expression);
    }
    if (preg_match('/^\$[a-zA-Z]+Html$/', $expression) === 1) {
        return true;
    }

    return isChoiceBetweenFixedTexts($expression);
}

/** True when the bracket opened by the first "(" is closed by the very last character. */
function isOneCall(string $expression): bool
{
    $depth = 0;
    $tokens = token_get_all('<?php ' . $expression);
    $lastIndex = count($tokens) - 1;

    foreach ($tokens as $index => $token) {
        if ($token === '(') {
            $depth++;
        } elseif ($token === ')') {
            $depth--;
            if ($depth === 0) {
                return $index === $lastIndex;
            }
        }
    }

    return false;
}

/**
 * True for "condition ? 'text' : 'text'", also nested, where every possible
 * result is a fixed text in quotes without "$". The condition may be anything.
 */
function isChoiceBetweenFixedTexts(string $expression): bool
{
    $tokens = token_get_all('<?php ' . $expression . ';');
    $sawQuestionMark = false;

    foreach ($tokens as $index => $token) {
        if ($token !== '?' && $token !== ':') {
            continue;
        }
        $sawQuestionMark = $sawQuestionMark || $token === '?';

        // The next real token after "?" or ":" must be a fixed text or "(" (a nested choice).
        $next = nextRealToken($tokens, $index + 1);
        $isFixedText = is_array($next) && $next[0] === T_CONSTANT_ENCAPSED_STRING && is_string($next[1]) && !str_contains($next[1], '$');
        if (!$isFixedText && $next !== '(') {
            return false;
        }
    }

    return $sawQuestionMark;
}

/**
 * @param array<int, mixed> $tokens
 */
function nextRealToken(array $tokens, int $from): mixed
{
    for ($index = $from; $index < count($tokens); $index++) {
        $token = $tokens[$index];
        if (is_array($token) && $token[0] === T_WHITESPACE) {
            continue;
        }

        return $token;
    }

    return null;
}

// --- Running the check ---

$problems = [];
foreach (listTemplates('templates') as $path) {
    $source = (string) file_get_contents($path);

    preg_match_all('/<\?=(.*?)\?>/s', $source, $outputs, PREG_OFFSET_CAPTURE);
    foreach ($outputs[1] as [$expression, $offset]) {
        if (!isAllowedOutput($expression)) {
            $line = substr_count(substr($source, 0, $offset), "\n") + 1;
            $problems[] = "{$path}:{$line}: <?=" . trim($expression) . ' ?> is not escaped with e()';
        }
    }

    foreach (token_get_all($source) as $token) {
        if (is_array($token) && ($token[0] === T_ECHO || $token[0] === T_PRINT)) {
            $problems[] = "{$path}:{$token[2]}: \"{$token[1]}\" is not allowed in templates; use <?= e(...) ?>";
        }
    }
}

if ($problems === []) {
    echo "Escaping check passed: every printed value in templates/ is escaped (K6).\n";
    exit(0);
}

echo "Escaping check failed (K6):\n";
foreach ($problems as $problem) {
    echo "  - {$problem}\n";
}
exit(1);
