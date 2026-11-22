<?php

declare(strict_types=1);

namespace PhpChess\Kernel;

use ErrorException;
use Throwable;

/**
 * Error handling: errors are logged, never shown (ARCHITECTURE.md §5).
 *
 * Turns every PHP warning and notice into an exception, so a mistake stops the
 * request instead of carrying on with a wrong value. The Kernel catches it,
 * writes it to storage/logs/ and answers with a plain "something went wrong".
 */
final class ErrorHandler
{
    public function __construct(private readonly string $logFolder)
    {
    }

    /** Switches error display off and turns warnings into exceptions. */
    public function register(): void
    {
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');
        ini_set('error_log', $this->logFolder . '/php-errors.log');
        error_reporting(E_ALL);

        set_error_handler(static function (int $level, string $message, string $file, int $line): bool {
            if ((error_reporting() & $level) === 0) {
                // The "@" operator was used: the caller handles the failure itself.
                return false;
            }
            throw new ErrorException($message, 0, $level, $file, $line);
        });
    }

    /** Writes one exception, with where it happened, to the error log. */
    public function log(Throwable $error): void
    {
        $line = sprintf(
            "[%s] %s: %s in %s:%d\n%s\n",
            gmdate('Y-m-d H:i:s'),
            $error::class,
            $error->getMessage(),
            $error->getFile(),
            $error->getLine(),
            $error->getTraceAsString(),
        );

        if (is_dir($this->logFolder) && is_writable($this->logFolder)) {
            file_put_contents($this->logFolder . '/errors.log', $line, FILE_APPEND | LOCK_EX);

            return;
        }
        error_log($line);
    }
}
