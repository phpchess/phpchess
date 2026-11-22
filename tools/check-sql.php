<?php

/**
 * SQL check (contract K5, ARCHITECTURE.md §4).
 *
 * All SQL goes through the repositories in src/Infra/, as prepared statements.
 * This script fails (exit code 1) when code outside src/Infra/ touches the
 * database directly, and when code inside src/Infra/ calls PDO's query() or
 * exec() with anything but a fixed text, which could carry input into SQL.
 *
 * Run from the project root:  php tools/check-sql.php
 */

declare(strict_types=1);

// --- Settings ---

$foldersToCheck = ['src', 'public', 'templates'];
$repositoryFolder = 'src' . DIRECTORY_SEPARATOR . 'Infra' . DIRECTORY_SEPARATOR;

/** Calls on a database connection ($database, $pdo) that only repository code may make. */
$databaseCalls = '/\$(this->)?(database|pdo)->(query|exec|prepare|run|fetchAll|fetchRow|fetchValue)\s*\(|new\s+PDO\s*\(/';

/** PDO calls inside src/Infra/ that build SQL from a variable instead of a fixed text. */
$unsafePdoCalls = '/->(query|exec)\s*\(\s*(?![\'"]\s*[A-Z])/';

// --- Finding the files ---

/**
 * @return list<string>
 */
function listPhpFiles(string $folder): array
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

// --- Checking ---

$problems = [];
foreach ($foldersToCheck as $folder) {
    foreach (listPhpFiles($folder) as $path) {
        $isRepositoryCode = str_starts_with($path, $repositoryFolder);
        $lines = file($path) ?: [];

        foreach ($lines as $index => $line) {
            $lineNumber = $index + 1;

            if (!$isRepositoryCode && preg_match($databaseCalls, $line) === 1) {
                // The Kernel and the installer only pass Database objects on; real queries stay in src/Infra/.
                $problems[] = "{$path}:{$lineNumber}: database call outside src/Infra/ (K5)";
            }

            if ($isRepositoryCode && preg_match($unsafePdoCalls, $line) === 1) {
                $problems[] = "{$path}:{$lineNumber}: PDO query()/exec() with a variable; use run() with parameters (K5)";
            }
        }
    }
}

// --- Result ---

if ($problems === []) {
    echo "SQL check passed: all SQL is in src/Infra/ and uses prepared statements.\n";
    exit(0);
}

echo "SQL check failed:\n";
foreach ($problems as $problem) {
    echo "  - {$problem}\n";
}
exit(1);
