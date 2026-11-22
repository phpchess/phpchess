<?php

/**
 * Outside-request check (contract K9, INS-10, A16).
 *
 * phpChess loads every script, style, font and image from its own server.
 * This script fails (exit code 1) when a file the browser receives (public/
 * and templates/) would make the browser fetch something from another host:
 * src="//...", href="https://...", url(https://...), @import, fetch(),
 * import ... from "https://...", new Worker/WebSocket/EventSource("https://...").
 *
 * Links in comments and plain text (for example a licence's home page) are
 * not requests and are allowed.
 *
 * Run from the project root:  php tools/check-external-urls.php
 */

declare(strict_types=1);

// --- Settings ---

$foldersToCheck = ['public', 'templates'];
$extensionsToCheck = ['php', 'html', 'js', 'mjs', 'css', 'svg', 'json', 'webmanifest'];

/** Each pattern is one way of making the browser load something. */
$requestPatterns = [
    'src or href attribute' => '/\b(src|href|action|poster|data)\s*=\s*["\']\s*(https?:)?\/\//i',
    'CSS url()'             => '/url\(\s*["\']?\s*(https?:)?\/\//i',
    'CSS @import'           => '/@import\s+(url\()?\s*["\']\s*(https?:)?\/\//i',
    'fetch() or import()'   => '/\b(fetch|import|importScripts)\s*\(\s*["\'`]\s*(https?:)?\/\//i',
    'import from'           => '/\bfrom\s+["\']\s*(https?:)?\/\//i',
    'worker or socket'      => '/new\s+(Worker|SharedWorker|WebSocket|EventSource)\s*\(\s*["\'`]\s*(wss?:|https?:)?\/\//i',
    'XMLHttpRequest open'   => '/\.open\s*\(\s*["\'][A-Z]+["\']\s*,\s*["\']\s*(https?:)?\/\//i',
];

// --- Finding the files ---

/**
 * @param list<string> $extensions
 * @return list<string>
 */
function listFilesWithExtensions(string $folder, array $extensions): array
{
    if (!is_dir($folder)) {
        return [];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file instanceof SplFileInfo && in_array(strtolower($file->getExtension()), $extensions, true)) {
            $files[] = $file->getPathname();
        }
    }
    sort($files);

    return $files;
}

// --- Checking ---

$problems = [];
foreach ($foldersToCheck as $folder) {
    foreach (listFilesWithExtensions($folder, $extensionsToCheck) as $path) {
        foreach (file($path) ?: [] as $index => $line) {
            foreach ($requestPatterns as $kind => $pattern) {
                if (preg_match($pattern, $line) === 1) {
                    $problems[] = "{$path}:" . ($index + 1) . ": {$kind} to another host";
                }
            }
        }
    }
}

// --- Result ---

if ($problems === []) {
    echo "Outside-request check passed: everything the browser loads is local (K9).\n";
    exit(0);
}

echo "Outside-request check failed (K9):\n";
foreach ($problems as $problem) {
    echo "  - {$problem}\n";
}
exit(1);
