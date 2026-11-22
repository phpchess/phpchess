<?php

/**
 * Header check (contract K15, ARCHITECTURE.md §11).
 *
 * Every PHP file in src/, tests/, public/, templates/, migrations/ and tools/ must start with a header comment
 * that says what the file is. This script lists the files that don't, and
 * exits with code 1 if there are any, so CI fails.
 *
 * Run from the project root:  php tools/check-headers.php
 */

declare(strict_types=1);

// --- Settings ---

$foldersToCheck = ['src', 'tests', 'public', 'templates', 'migrations', 'tools'];

// --- Finding the PHP files ---

/**
 * Returns every .php file below the given folder.
 *
 * @return list<string>
 */
function findPhpFiles(string $folder): array
{
    if (!is_dir($folder)) {
        return [];
    }

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

// --- Checking one file ---

/**
 * A file passes when, after the opening "<?php" and an optional
 * "declare(strict_types=1);" and namespace/use lines, the first real content
 * is a doc comment (starting with "/**").
 */
function hasHeaderComment(string $path): bool
{
    $tokens = token_get_all((string) file_get_contents($path));

    foreach ($tokens as $token) {
        if (!is_array($token)) {
            // A plain character such as ";" from declare(...); keep looking.
            continue;
        }

        [$type] = $token;

        if ($type === T_DOC_COMMENT) {
            return true;
        }

        $skippable = [T_OPEN_TAG, T_WHITESPACE, T_DECLARE, T_STRING, T_LNUMBER,
            T_NAMESPACE, T_NAME_QUALIFIED, T_USE, T_COMMENT];
        if (!in_array($type, $skippable, true)) {
            // Real code (a class, a function, an echo) came before any header.
            return false;
        }
    }

    return false;
}

// --- Running the check ---

$missing = [];
foreach ($foldersToCheck as $folder) {
    foreach (findPhpFiles($folder) as $path) {
        if (!hasHeaderComment($path)) {
            $missing[] = $path;
        }
    }
}

if ($missing === []) {
    echo "Header check passed: every PHP file has a header comment.\n";
    exit(0);
}

echo "These files have no header comment (see ARCHITECTURE.md §11):\n";
foreach ($missing as $path) {
    echo "  - {$path}\n";
}
exit(1);
