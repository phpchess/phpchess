<?php

declare(strict_types=1);

namespace PhpChess\Domain\Tournament;

/**
 * A tournament calendar as an .ics file (A117), the standard calendar
 * format (RFC 5545) that phones and calendar programs import.
 *
 * One event per round, from its start to its deadline, with a link to the
 * tournament. Made by our own server: no outside service (A16).
 * Pure code, no database.
 */
final class IcsCalendar
{
    /**
     * @param list<array{round: int, start: int, deadline: int}> $rounds
     */
    public static function of(string $tournamentName, int $tournamentId, array $rounds, string $tournamentUrl, string $host, int $now): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//phpChess//Tournament calendar//EN',
            'CALSCALE:GREGORIAN',
        ];
        foreach ($rounds as $round) {
            $lines[] = 'BEGIN:VEVENT';
            // A stable id: importing the file again updates the events instead of doubling them.
            $lines[] = 'UID:tournament-' . $tournamentId . '-round-' . $round['round'] . '@' . self::escape($host);
            $lines[] = 'DTSTAMP:' . self::utc($now);
            $lines[] = 'DTSTART:' . self::utc($round['start']);
            $lines[] = 'DTEND:' . self::utc(max($round['deadline'], $round['start'] + 60));
            $lines[] = 'SUMMARY:' . self::escape($tournamentName . ' - round ' . $round['round']);
            $lines[] = 'URL:' . self::escape($tournamentUrl);
            $lines[] = 'END:VEVENT';
        }
        $lines[] = 'END:VCALENDAR';

        // The format wants CR LF line ends and lines of at most 75 bytes.
        return implode("\r\n", array_map(self::fold(...), $lines)) . "\r\n";
    }

    /** A time in UTC, as the format writes it: 20261201T180000Z. */
    private static function utc(int $time): string
    {
        return gmdate('Ymd\THis\Z', $time);
    }

    /** Backslash, comma, semicolon and new lines are escaped in text values. */
    private static function escape(string $text): string
    {
        return str_replace(['\\', ';', ',', "\r", "\n"], ['\\\\', '\;', '\,', '', '\n'], $text);
    }

    /** Long lines go on in the next line, which starts with a space (RFC 5545, 3.1). */
    private static function fold(string $line): string
    {
        if (strlen($line) <= 75) {
            return $line;
        }
        $parts = [];
        while (strlen($line) > 75) {
            $cut = 75;
            // Never cut inside a UTF-8 letter: step back to the start of one.
            while ($cut > 1 && (ord($line[$cut]) & 0xC0) === 0x80) {
                $cut--;
            }
            $parts[] = substr($line, 0, $cut);
            $line = ' ' . substr($line, $cut);
        }
        $parts[] = $line;

        return implode("\r\n", $parts);
    }
}
