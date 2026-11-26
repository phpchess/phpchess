<?php

declare(strict_types=1);

namespace PhpChess\Domain\Lichess;

use PhpChess\Domain\Chess\Pgn;

/**
 * One game as PgnReader read it: its tags, its start position and its
 * moves, already replayed with our rules. Comments, variations and signs
 * are gone (A81).
 */
final class ReadGame
{
    /**
     * @param array<string, string> $tags  tag name => value, as found in the file
     * @param string $startFen  the start position (Position::START_FEN for a normal game)
     * @param list<string> $sanMoves  the moves in SAN, all legal
     * @param string $result  "1-0", "0-1", "1/2-1/2" or "*"
     */
    public function __construct(
        public readonly array $tags,
        public readonly string $startFen,
        public readonly array $sanMoves,
        public readonly string $result,
    ) {
    }

    /** The value of a tag, or '' when the game doesn't have it. */
    public function tag(string $name): string
    {
        return $this->tags[$name] ?? '';
    }

    /**
     * A copy with only these tags, in the order of the list (the scanner's
     * allowed tags). Tags with an empty value are left out.
     *
     * @param list<string> $allowedNames
     */
    public function withOnlyTags(array $allowedNames): self
    {
        $kept = [];
        foreach ($allowedNames as $name) {
            if (($this->tags[$name] ?? '') !== '') {
                $kept[$name] = $this->tags[$name];
            }
        }

        return new self($kept, $this->startFen, $this->sanMoves, $this->result);
    }

    /** The game written out again as clean PGN: allowed tags and moves only. */
    public function toPgn(): string
    {
        // SetUp and FEN are written by Pgn::write from the start position.
        $tags = $this->tags;
        unset($tags['SetUp'], $tags['FEN']);
        $tags['Result'] = $this->result;

        return Pgn::write($tags, $this->startFen, $this->sanMoves);
    }
}
