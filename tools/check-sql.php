<?php

/**
 * SQL check (contract K5, ARCHITECTURE.md §4).
 *
 * All SQL goes through the repositories in src/Infra/, as prepared statements.
 * This script fails (exit code 1) when code outside src/Infra/ touches the
 * database directly, and when code inside src/Infra/ calls PDO's query() or
 * exec() with anything but a fixed text, which could carry input into SQL.
 *
 * It also keeps contract K3: only GameRepository::writeResult() writes a
 * game's result, and only the game-end code in Games.php calls it.
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

/** The one file allowed to write games.result, and the one allowed to call writeResult() (K3). */
$resultWriterFile = 'src' . DIRECTORY_SEPARATOR . 'Infra' . DIRECTORY_SEPARATOR . 'Play' . DIRECTORY_SEPARATOR . 'GameRepository.php';
$gameEndFile = 'src' . DIRECTORY_SEPARATOR . 'Domain' . DIRECTORY_SEPARATOR . 'Play' . DIRECTORY_SEPARATOR . 'Games.php';

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

            if ($path !== $resultWriterFile && preg_match('/\bSET\b.*\bresult\s*=/', $line) === 1) {
                $problems[] = "{$path}:{$lineNumber}: writes a game result outside GameRepository::writeResult() (K3)";
            }
            if ($path !== $gameEndFile && $path !== $resultWriterFile && str_contains($line, '->writeResult(')) {
                $problems[] = "{$path}:{$lineNumber}: calls writeResult() outside Games::end() (K3)";
            }
        }
    }
}

// --- Result ---

if ($problems === []) {
    echo "SQL check passed: all SQL is in src/Infra/ and uses prepared statements; only Games::end() writes game results (K3).\n";
    exit(0);
}

echo "SQL check failed:\n";
foreach ($problems as $problem) {
    echo "  - {$problem}\n";
}
exit(1);
