<?php

declare(strict_types=1);

namespace PhpChess\Infra\Lichess;

use Closure;

/**
 * Keeps Lichess's answers for a while, so many viewers share one request (A96).
 *
 * Every answer is a file in storage/cache/lichess/, named after what was
 * asked. While the file is younger than the given seconds it is used; after
 * that Lichess is asked again. If asking fails, the error goes to the page
 * and the old file is left alone.
 *
 * How long each kind is kept is decided by the caller: live games 10
 * seconds, broadcasts 5 minutes, the daily puzzle 1 hour.
 */
final class LichessCache
{
    public function __construct(
        private readonly string $folder,
        private readonly int $now,
    ) {
    }

    /**
     * The answer for $name: from the cache while it is fresh, otherwise
     * from $ask (which talks to Lichess), then kept.
     *
     * @param Closure(): string $ask
     */
    public function remember(string $name, int $seconds, Closure $ask): string
    {
        $file = $this->folder . '/' . md5($name) . '.txt';
        $modifiedAt = is_file($file) ? filemtime($file) : false;
        if ($modifiedAt !== false && $modifiedAt > $this->now - $seconds) {
            return (string) file_get_contents($file);
        }

        $answer = $ask();
        if (!is_dir($this->folder)) {
            mkdir($this->folder, 0775, true);
        }
        file_put_contents($file, $answer, LOCK_EX);
        touch($file, $this->now);

        return $answer;
    }
}
