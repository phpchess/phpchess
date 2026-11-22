<?php

declare(strict_types=1);

namespace PhpChess\Tests\Support;

/**
 * A throw-away install folder for installer and kernel tests.
 *
 * Creates a temporary folder shaped like a real install (config/, storage/logs/,
 * templates/, migrations/), with the templates and migrations copied from this
 * repository, so tests can write config files without touching the real ones.
 */
final class TestInstallFolder
{
    public readonly string $path;

    public function __construct()
    {
        $this->path = sys_get_temp_dir() . '/phpchess-test-' . bin2hex(random_bytes(6));
        mkdir($this->path . '/config', 0777, true);
        mkdir($this->path . '/storage/logs', 0777, true);

        $repository = dirname(__DIR__, 2);
        self::copyFolder($repository . '/templates', $this->path . '/templates');
        self::copyFolder($repository . '/migrations', $this->path . '/migrations');
    }

    /** Deletes the folder and everything in it. */
    public function remove(): void
    {
        self::deleteFolder($this->path);
    }

    private static function copyFolder(string $from, string $to): void
    {
        mkdir($to, 0777, true);
        foreach (scandir($from) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if (is_dir($from . '/' . $entry)) {
                self::copyFolder($from . '/' . $entry, $to . '/' . $entry);
                continue;
            }
            copy($from . '/' . $entry, $to . '/' . $entry);
        }
    }

    private static function deleteFolder(string $folder): void
    {
        foreach (scandir($folder) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $folder . '/' . $entry;
            if (is_dir($path)) {
                self::deleteFolder($path);
                continue;
            }
            unlink($path);
        }
        rmdir($folder);
    }
}
