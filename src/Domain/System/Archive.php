<?php

declare(strict_types=1);

namespace PhpChess\Domain\System;

use PhpChess\Domain\ActionRefused;
use PhpChess\Infra\System\ArchiveRepository;
use PhpChess\Infra\System\SettingsRepository;

/**
 * Keeping the database fast without losing history (SCH-07, A42, A43).
 *
 * Nothing is ever deleted because of its age. Instead:
 *   1. Every day the server times a few typical reads (check()). When the
 *      slowest takes longer than the cut-off (setting "archive.cutoff_ms",
 *      0.5 s by default), it suggests an archive: on the admin dashboard
 *      and by email to the admins (PerformanceCheckJob).
 *   2. The admin picks a date and writes an archive file (write()): the
 *      old rows as compressed JSON, one compact line per row. The admin
 *      downloads it and keeps it on their own computer.
 *   3. Only then can the admin remove those rows (remove()). It refuses
 *      unless the archive file for that date was written first.
 *   4. A kept file can be read back in at any time (restore()).
 *
 * The file's first line says what it is: {"phpchess_archive":1,"before":"..."}.
 * Each other line is {"t":"<table>","r":{<row>}}.
 */
final class Archive
{
    public const int DEFAULT_CUTOFF_MS = 500;
    public const int FORMAT_VERSION = 1;

    public function __construct(
        private readonly ArchiveRepository $archive,
        private readonly SettingsRepository $settings,
        private readonly string $archiveFolder,
    ) {
    }

    // --- 1. Measuring ---

    /**
     * Times the typical reads, remembers the result and says whether they are too slow.
     *
     * @return array{times: array<string, int>, slowest_ms: int, cutoff_ms: int, too_slow: bool}
     */
    public function check(int $now): array
    {
        $times = $this->archive->timeTypicalReads();
        $slowest = $times === [] ? 0 : max($times);
        $cutoff = $this->cutoffMs();
        $this->settings->set('archive.last_check_at', $now);
        $this->settings->set('archive.last_slowest_ms', $slowest);
        $this->settings->set('archive.too_slow', $slowest > $cutoff);

        return ['times' => $times, 'slowest_ms' => $slowest, 'cutoff_ms' => $cutoff, 'too_slow' => $slowest > $cutoff];
    }

    public function cutoffMs(): int
    {
        return max(50, $this->settings->getInt('archive.cutoff_ms', self::DEFAULT_CUTOFF_MS));
    }

    /** True when the last check found the database too slow (shown on the admin dashboard). */
    public function isSuggested(): bool
    {
        return $this->settings->getBool('archive.too_slow');
    }

    /**
     * How many rows an archive before this date would hold, per table.
     *
     * @return array<string, int>
     */
    public function preview(string $beforeDate): array
    {
        return $this->archive->countOlderThan(self::checkedDate($beforeDate) . ' 00:00:00');
    }

    // --- 2. Writing the file ---

    /**
     * Writes the archive file for rows older than the date; returns its path and row count.
     *
     * @return array{path: string, rows: int}
     */
    public function write(string $beforeDate, int $now): array
    {
        $date = self::checkedDate($beforeDate);
        if (!is_dir($this->archiveFolder)) {
            mkdir($this->archiveFolder, 0775, true);
        }
        $path = $this->fileFor($date);
        $file = gzopen($path, 'wb9');
        if ($file === false) {
            throw new ActionRefused('archive.cannot_write');
        }
        gzwrite($file, self::line(['phpchess_archive' => self::FORMAT_VERSION, 'before' => $date, 'written_at' => gmdate('Y-m-d H:i:s', $now)]));
        $rows = $this->archive->eachOldRow($date . ' 00:00:00', function (string $table, array $row) use ($file): void {
            gzwrite($file, self::line(['t' => $table, 'r' => $row]));
        });
        gzclose($file);
        $this->settings->set('archive.written_before', $date);

        return ['path' => $path, 'rows' => $rows];
    }

    /** The archive file for a date, or '' when it hasn't been written. */
    public function existingFile(string $beforeDate): string
    {
        $path = $this->fileFor(self::checkedDate($beforeDate));

        return is_file($path) ? $path : '';
    }

    // --- 3. Removing the archived rows ---

    /** Removes the rows older than the date; refuses unless their archive file was written first. */
    public function remove(string $beforeDate): int
    {
        $date = self::checkedDate($beforeDate);
        if ($this->existingFile($date) === '' || $this->settings->getString('archive.written_before') !== $date) {
            throw new ActionRefused('archive.write_first');
        }
        $removed = $this->archive->deleteOlderThan($date . ' 00:00:00');
        $this->settings->set('archive.too_slow', false);

        return $removed;
    }

    // --- 4. Reading a file back in ---

    /**
     * Reads an archive file back into the database.
     *
     * @return array{restored: int, skipped: int}
     */
    public function restore(string $path): array
    {
        $file = @gzopen($path, 'rb');
        if ($file === false) {
            throw new ActionRefused('archive.not_an_archive');
        }
        $header = json_decode((string) gzgets($file), true);
        if (!is_array($header) || ($header['phpchess_archive'] ?? null) !== self::FORMAT_VERSION) {
            gzclose($file);
            throw new ActionRefused('archive.not_an_archive');
        }

        $restored = 0;
        $skipped = 0;
        while (($line = gzgets($file)) !== false) {
            $entry = json_decode($line, true);
            if (!is_array($entry) || !is_string($entry['t'] ?? null) || !is_array($entry['r'] ?? null)) {
                $skipped++;
                continue;
            }
            /** @var array<string, mixed> $row */
            $row = $entry['r'];
            if ($this->archive->restoreRow($entry['t'], $row)) {
                $restored++;
            } else {
                $skipped++;
            }
        }
        gzclose($file);

        return ['restored' => $restored, 'skipped' => $skipped];
    }

    // --- Helpers ---

    private function fileFor(string $date): string
    {
        return $this->archiveFolder . '/archive-before-' . $date . '.jsonl.gz';
    }

    /** A date as YYYY-MM-DD, or ActionRefused. */
    public static function checkedDate(string $date): string
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1 || strtotime($date . ' UTC') === false) {
            throw new ActionRefused('archive.bad_date');
        }

        return $date;
    }

    /**
     * One compact line of JSON.
     *
     * @param array<string, mixed> $data
     */
    private static function line(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) . "\n";
    }
}
