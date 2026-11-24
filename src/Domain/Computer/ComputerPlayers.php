<?php

declare(strict_types=1);

namespace PhpChess\Domain\Computer;

use RuntimeException;

/**
 * The list of computer players (AI-03, decision A53), read from
 * computer-players.json next to this file.
 *
 * The file is edited by people (names, bios) and by
 * tools/build-player-settings.mjs (the engine settings); this class only reads it.
 */
final class ComputerPlayers
{
    /** @var array<int, ComputerPlayer>|null  by id, read once */
    private ?array $players = null;

    public function __construct(private readonly string $file = __DIR__ . '/computer-players.json')
    {
    }

    /**
     * Every computer player, weakest first (the Wood Pusher first).
     *
     * @return list<ComputerPlayer>
     */
    public function all(): array
    {
        $players = array_values($this->byId());
        usort($players, fn (ComputerPlayer $first, ComputerPlayer $second): int => [$first->elo, $first->id] <=> [$second->elo, $second->id]);

        return $players;
    }

    /** One player, or null when there is no player with that id. */
    public function find(int $id): ?ComputerPlayer
    {
        return $this->byId()[$id] ?? null;
    }

    // --- Reading the file ---

    /**
     * @return array<int, ComputerPlayer>
     */
    private function byId(): array
    {
        if ($this->players !== null) {
            return $this->players;
        }
        $text = file_get_contents($this->file);
        $rows = $text === false ? null : json_decode($text, true);
        if (!is_array($rows)) {
            throw new RuntimeException('The computer player list can not be read: ' . $this->file);
        }

        $this->players = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $player = self::playerFrom($row);
            $this->players[$player->id] = $player;
        }

        return $this->players;
    }

    /**
     * @param array<mixed> $row
     */
    private static function playerFrom(array $row): ComputerPlayer
    {
        $engine = is_array($row['engine'] ?? null) ? $row['engine'] : [];
        $age = $row['age'] ?? null;

        /** @var array<string, mixed> $engine */
        return new ComputerPlayer(
            id: self::number($row['id'] ?? 0),
            name: self::text($row['name'] ?? ''),
            personality: self::text($row['personality'] ?? ''),
            elo: self::number($row['elo'] ?? 0),
            age: $age === null ? null : self::number($age),
            bio: self::text($row['bio'] ?? ''),
            engine: $engine,
            slowOnPhones: ($engine['slowOnPhones'] ?? false) === true,
        );
    }

    private static function number(mixed $value): int
    {
        return is_int($value) ? $value : (is_numeric($value) ? (int) $value : 0);
    }

    private static function text(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }
}
