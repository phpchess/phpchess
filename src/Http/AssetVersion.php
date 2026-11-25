<?php

declare(strict_types=1);

namespace PhpChess\Http;

use FilesystemIterator;
use PhpChess\Version;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * The service worker's cache name (B20, A62): the version plus a short
 * fingerprint of the files under public/assets/.
 *
 * Before F1 the name was typed into the service worker by hand, and a
 * release that forgot it kept serving old scripts from the phones' caches.
 * Now any new, removed or changed asset file (another size or date) gives
 * a new name, so the browser drops the old cache by itself.
 *
 * The fingerprint reads only file names, sizes and dates (no contents),
 * so it is quick even with a few hundred files.
 */
final class AssetVersion
{
    /** For example "phpchess-6.0.0-dev-3f9a1c2b". */
    public static function cacheName(string $assetFolder): string
    {
        return 'phpchess-' . self::safe(Version::CURRENT) . '-' . self::fingerprint($assetFolder);
    }

    /** Eight hex letters that change whenever an asset file changes. */
    public static function fingerprint(string $assetFolder): string
    {
        if (!is_dir($assetFolder)) {
            return '00000000';
        }

        $lines = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($assetFolder, FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile()) {
                $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($assetFolder)));
                $lines[] = $relativePath . ' ' . $file->getSize() . ' ' . $file->getMTime();
            }
        }
        // The folder order differs between systems; sorting makes it the same everywhere.
        sort($lines);

        return hash('crc32b', implode("\n", $lines));
    }

    /** Only letters, digits, dots and dashes, so the name is safe inside the script. */
    private static function safe(string $text): string
    {
        return (string) preg_replace('/[^A-Za-z0-9.-]/', '-', $text);
    }
}
