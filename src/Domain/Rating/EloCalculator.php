<?php

declare(strict_types=1);

namespace PhpChess\Domain\Rating;

/**
 * The Elo formula (R-01, R-02, A30).
 *
 * The expected score of a player against an opponent is
 *     1 / (1 + 10 ^ ((opponent - player) / 400)),
 * and after the game the rating changes by K * (score - expected), where the
 * score is 1 for a win, 0.5 for a draw and 0 for a loss.
 *
 * K depends on experience (A30): K = 40 while a player has played fewer than
 * 20 rated games at this speed (provisional), K = 20 after that.
 */
final class EloCalculator
{
    /** Games before a rating is shown at all ("newbie", A30). */
    public const int NEWBIE_GAMES = 3;

    /** Games before a rating stops being provisional (A30). */
    public const int PROVISIONAL_GAMES = 20;

    public const int PROVISIONAL_K = 40;
    public const int ESTABLISHED_K = 20;

    /** The chance-weighted score a player is expected to make, between 0 and 1. */
    public static function expectedScore(int $playerRating, int $opponentRating): float
    {
        return 1 / (1 + 10 ** (($opponentRating - $playerRating) / 400));
    }

    /** The K factor for a player who has played this many rated games before this one. */
    public static function kFactor(int $gamesPlayedBefore): int
    {
        return $gamesPlayedBefore < self::PROVISIONAL_GAMES ? self::PROVISIONAL_K : self::ESTABLISHED_K;
    }

    /**
     * The new rating after one game.
     *
     * @param float $score  1 win, 0.5 draw, 0 loss
     */
    public static function newRating(int $playerRating, int $opponentRating, float $score, int $gamesPlayedBefore): int
    {
        $change = self::kFactor($gamesPlayedBefore) * ($score - self::expectedScore($playerRating, $opponentRating));

        return (int) round($playerRating + $change);
    }
}
