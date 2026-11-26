<?php

declare(strict_types=1);

namespace PhpChess\Domain\Puzzles;

/**
 * The bundled puzzles (C5-1, L-01, A76, A80).
 *
 * About 3,300 puzzles from the Lichess puzzle database (CC0), picked by
 * tools/build-puzzle-set.php and kept in puzzle-set.csv next to this file.
 * They ship in the release, so puzzles work without any outside request,
 * even in an offline school: no switch is needed.
 *
 * A player picks a motif (fork, pin, mate in 2 ...) and a level; next()
 * gives a random puzzle that fits, avoiding the ones just shown.
 */
final class PuzzleSet
{
    /** The motifs players can choose, in menu order. "any" means all. */
    public const array MOTIFS = [
        'any', 'mateIn1', 'mateIn2', 'mateIn3', 'fork', 'pin', 'skewer',
        'discoveredAttack', 'hangingPiece', 'sacrifice', 'deflection',
        'attraction', 'backRankMate', 'promotion', 'endgame', 'opening',
    ];

    /** The levels: name => lowest and highest puzzle rating. */
    public const array LEVELS = [
        'beginner' => [600, 999],
        'improving' => [1000, 1399],
        'club' => [1400, 1799],
        'strong' => [1800, 2199],
        'expert' => [2200, 2599],
    ];

    /** @var list<array{id: string, fen: string, moves: list<string>, rating: int, themes: list<string>, game: string}>|null */
    private ?array $puzzles = null;

    public function __construct(private readonly string $csvFile = __DIR__ . '/puzzle-set.csv')
    {
    }

    /**
     * A random puzzle for this motif and level, or null when none fits.
     * Puzzles in $recentIds are skipped while others are left.
     *
     * @param list<string> $recentIds
     * @return array{id: string, fen: string, moves: list<string>, rating: int, themes: list<string>, game: string}|null
     */
    public function next(string $motif, string $level, array $recentIds = []): ?array
    {
        [$lowest, $highest] = self::LEVELS[$level] ?? self::LEVELS['improving'];
        $fitting = [];
        foreach ($this->all() as $puzzle) {
            $motifFits = $motif === 'any' || in_array($motif, $puzzle['themes'], true);
            if ($motifFits && $puzzle['rating'] >= $lowest && $puzzle['rating'] <= $highest) {
                $fitting[] = $puzzle;
            }
        }
        if ($fitting === []) {
            return null;
        }

        $fresh = array_values(array_filter($fitting, static fn (array $puzzle): bool => !in_array($puzzle['id'], $recentIds, true)));
        $choices = $fresh === [] ? $fitting : $fresh;

        return $choices[random_int(0, count($choices) - 1)];
    }

    /** How many puzzles the set holds. */
    public function count(): int
    {
        return count($this->all());
    }

    /**
     * Every puzzle, read from the file once.
     *
     * @return list<array{id: string, fen: string, moves: list<string>, rating: int, themes: list<string>, game: string}>
     */
    private function all(): array
    {
        if ($this->puzzles !== null) {
            return $this->puzzles;
        }

        $this->puzzles = [];
        $file = fopen($this->csvFile, 'r');
        if ($file === false) {
            return $this->puzzles;
        }
        fgetcsv($file, null, ',', '"', ''); // skip the header line: id,fen,moves,rating,themes,game
        while (($row = fgetcsv($file, null, ',', '"', '')) !== false) {
            if (count($row) < 6) {
                continue;
            }
            $this->puzzles[] = [
                'id' => (string) $row[0],
                'fen' => (string) $row[1],
                'moves' => explode(' ', (string) $row[2]),
                'rating' => (int) $row[3],
                'themes' => explode(' ', (string) $row[4]),
                'game' => (string) $row[5],
            ];
        }
        fclose($file);

        return $this->puzzles;
    }
}
