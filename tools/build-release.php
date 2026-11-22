<?php

/**
 * Builds the release zip (INS-01, contract K10, ARCHITECTURE.md §10).
 *
 * The zip holds everything a host needs, so no Composer, npm or shell is
 * needed there (A17): the code, the templates, the migrations, public/ with
 * its JavaScript and vendored libraries, vendor/ installed with --no-dev, and
 * every .htaccess file. It never holds tests/, tools/, docs/ or development
 * packages; the build stops with exit code 1 if it would (K10).
 *
 * Run from the project root:
 *     php tools/build-release.php [--composer="php tools/composer.phar"]
 * Result: build/phpchess-<version>.zip, with one top folder "phpchess".
 */

declare(strict_types=1);

// --- Settings ---

/** What goes into the release, relative to the project root. */
$includedPaths = [
    '.htaccess', 'LICENSE', 'README.md', 'composer.json', 'composer.lock',
    'public', 'src', 'templates', 'migrations', 'lang', 'skins',
    'config/.htaccess', 'storage/.htaccess', 'storage/logs/.gitkeep',
];

/** Never released, even when inside an included folder. */
$excludedPaths = ['public/proof', 'templates/local', 'config/config.php'];

/** Folders that must not be in the release (K10). */
$forbiddenFolders = ['tests', 'tools', 'docs', '.github', 'vendor/phpunit', 'vendor/phpstan'];

$composerCommand = 'composer';
foreach ($argv as $argument) {
    if (str_starts_with($argument, '--composer=')) {
        $composerCommand = substr($argument, strlen('--composer='));
    }
}

$projectRoot = dirname(__DIR__);
require $projectRoot . '/src/Version.php';
$version = PhpChess\Version::CURRENT;

$buildFolder = $projectRoot . '/build';
$stagingFolder = $buildFolder . '/release';
$releaseFolder = $stagingFolder . '/phpchess';
$zipPath = $buildFolder . "/phpchess-{$version}.zip";

// --- Helpers ---

function stopWithError(string $message): never
{
    fwrite(STDERR, "Release build failed: {$message}\n");
    exit(1);
}

function deleteFolder(string $folder): void
{
    if (!is_dir($folder)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($iterator as $item) {
        if (!$item instanceof SplFileInfo) {
            continue;
        }
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($folder);
}

/**
 * Copies a file or a folder, skipping the excluded paths.
 *
 * @param list<string> $excludedPaths
 */
function copyPath(string $projectRoot, string $relativePath, string $targetRoot, array $excludedPaths): void
{
    if (in_array($relativePath, $excludedPaths, true)) {
        return;
    }

    $source = $projectRoot . '/' . $relativePath;
    $target = $targetRoot . '/' . $relativePath;

    if (is_file($source)) {
        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), 0755, true);
        }
        copy($source, $target);

        return;
    }
    if (!is_dir($source)) {
        stopWithError("{$relativePath} does not exist.");
    }

    foreach (scandir($source) ?: [] as $entry) {
        if ($entry !== '.' && $entry !== '..') {
            copyPath($projectRoot, $relativePath . '/' . $entry, $targetRoot, $excludedPaths);
        }
    }
}

// --- 1. A clean staging folder with the release files ---

echo "Building phpChess {$version}\n";
deleteFolder($stagingFolder);
if (is_file($zipPath)) {
    unlink($zipPath);
}
mkdir($releaseFolder, 0755, true);

foreach ($includedPaths as $relativePath) {
    copyPath($projectRoot, $relativePath, $releaseFolder, $excludedPaths);
}

// --- 2. vendor/ without development packages (K10) ---

$command = $composerCommand . ' install --no-dev --no-interaction --no-progress --working-dir='
    . escapeshellarg($releaseFolder);
echo "Running: {$command}\n";
passthru($command, $exitCode);
if ($exitCode !== 0) {
    stopWithError('composer install --no-dev failed.');
}
file_put_contents(
    $releaseFolder . '/vendor/.htaccess',
    "# phpChess: this folder is private and never served to the web (A17, INS-04).\nRequire all denied\n",
);

// --- 3. Checking the release (K10) ---

foreach ($forbiddenFolders as $folder) {
    if (file_exists($releaseFolder . '/' . $folder)) {
        stopWithError("{$folder}/ must not be in the release (K10).");
    }
}

$installedFile = $releaseFolder . '/vendor/composer/installed.json';
$installed = is_file($installedFile) ? json_decode((string) file_get_contents($installedFile), true) : null;
if (!is_array($installed) || ($installed['dev'] ?? true) !== false || ($installed['dev-package-names'] ?? []) !== []) {
    stopWithError('vendor/ contains development packages (K10).');
}

// --- 4. The zip ---

$zip = new PharData($zipPath, 0, null, Phar::ZIP);
$zip->buildFromDirectory($stagingFolder);

echo "Release written: {$zipPath}\n";
