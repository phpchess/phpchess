<?php

declare(strict_types=1);

namespace PhpChess\Kernel;

use RuntimeException;

/**
 * The install's configuration: database details and a few fixed choices.
 *
 * Written by the installer to config/config.php (never committed, never in the
 * release zip). The file returns a plain PHP array, so an admin can read and
 * correct it with any text editor. Everything else is a setting in the
 * database (System module), changed from the admin pages.
 *
 * "installed" is false while the installer is still running and true once it
 * has locked itself (INS-06).
 */
final class Config
{
    public function __construct(
        public readonly string $databaseHost,
        public readonly int $databasePort,
        public readonly string $databaseName,
        public readonly string $databaseUser,
        public readonly string $databasePassword,
        public readonly string $tablePrefix = 'pc_',
        public readonly bool $installed = false,
        public readonly string $installerOwnerHash = '',
    ) {
        if (preg_match('/^[a-z][a-z0-9_]{0,19}$/', $tablePrefix) !== 1) {
            throw new RuntimeException('The table prefix may only hold a-z, 0-9 and "_", and starts with a letter.');
        }
    }

    // --- Reading and writing the file ---

    /** The path of the config file inside an install. */
    public static function filePath(string $appRoot): string
    {
        return $appRoot . '/config/config.php';
    }

    /** Reads config/config.php, or returns null when the file doesn't exist yet. */
    public static function load(string $appRoot): ?self
    {
        $path = self::filePath($appRoot);
        if (!is_file($path)) {
            return null;
        }

        $values = require $path;
        if (!is_array($values)) {
            throw new RuntimeException('config/config.php must return an array.');
        }

        return self::fromArray($values);
    }

    /**
     * Builds the config from the array in the file; missing values get defaults.
     *
     * @param array<mixed> $values
     */
    public static function fromArray(array $values): self
    {
        return new self(
            databaseHost: self::text($values, 'database_host', 'localhost'),
            databasePort: (int) self::text($values, 'database_port', '3306'),
            databaseName: self::text($values, 'database_name', ''),
            databaseUser: self::text($values, 'database_user', ''),
            databasePassword: self::text($values, 'database_password', ''),
            tablePrefix: self::text($values, 'table_prefix', 'pc_'),
            installed: ($values['installed'] ?? false) === true,
            installerOwnerHash: self::text($values, 'installer_owner_hash', ''),
        );
    }

    /** Returns a copy with other values for the installer's state. */
    public function withInstallState(bool $installed, string $installerOwnerHash): self
    {
        return new self(
            $this->databaseHost,
            $this->databasePort,
            $this->databaseName,
            $this->databaseUser,
            $this->databasePassword,
            $this->tablePrefix,
            $installed,
            $installerOwnerHash,
        );
    }

    /** The text of config/config.php for these values. */
    public function toFileContents(): string
    {
        $values = [
            'database_host' => $this->databaseHost,
            'database_port' => $this->databasePort,
            'database_name' => $this->databaseName,
            'database_user' => $this->databaseUser,
            'database_password' => $this->databasePassword,
            'table_prefix' => $this->tablePrefix,
            'installed' => $this->installed,
            'installer_owner_hash' => $this->installerOwnerHash,
        ];

        return "<?php\n\n"
            . "/**\n"
            . " * phpChess configuration, written by the installer.\n"
            . " * Keep this file private: it holds the database password.\n"
            . " */\n\n"
            . 'return ' . var_export($values, true) . ";\n";
    }

    /**
     * @param array<mixed> $values
     */
    private static function text(array $values, string $name, string $default): string
    {
        $value = $values[$name] ?? $default;

        return is_scalar($value) ? (string) $value : $default;
    }
}
