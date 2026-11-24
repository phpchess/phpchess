<?php

declare(strict_types=1);

namespace PhpChess\Domain\Computer;

/**
 * One computer player from computer-players.json (AI-03, decision A53).
 *
 * The name, personality, Elo, age and bio come from the user's Arc Chess
 * project; the engine block (settings for the browser's engine) is written
 * by tools/build-player-settings.mjs. The Elo shown is the listed one; the
 * calibration changes the engine settings, never this number (A53).
 */
final class ComputerPlayer
{
    public const string PERSONALITY_RANDOM = 'Random';

    /**
     * @param array<string, mixed> $engine  the engine block, passed unchanged to the browser
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $personality,
        public readonly int $elo,
        public readonly ?int $age,
        public readonly string $bio,
        public readonly array $engine,
        public readonly bool $slowOnPhones,
    ) {
    }

    /**
     * The username of this player's account: the name, clearly labelled as
     * a computer (A8, A53). Member usernames can't contain brackets, so it
     * can never clash with a person.
     */
    public function username(): string
    {
        return $this->name . ' (computer)';
    }

    /** The file name of this player's face: "007.jpg" for player 7. */
    public function faceFile(): string
    {
        return sprintf('%03d.jpg', $this->id);
    }
}
