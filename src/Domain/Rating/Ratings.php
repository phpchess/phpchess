<?php

declare(strict_types=1);

namespace PhpChess\Domain\Rating;

use PhpChess\Domain\Play\Game;
use PhpChess\Infra\Rating\RatingRepository;
use PhpChess\Infra\System\SettingsRepository;

/**
 * Ratings per speed (R-01 to R-05, A30, contract K4).
 *
 * Called by Games::end() inside its transaction, after the result is
 * written. A rating is applied once per game and player: the history row is
 * written first, and when it already exists (rating_history is unique per
 * game and user) nothing changes. Unrated games (R-05) and aborted games
 * never change a rating, whichever way they end.
 *
 * Start value: the admin setting rating.start_value (default 600, A30), or
 * the strength band the player chose at registration.
 */
final class Ratings
{
    public const int DEFAULT_START_VALUE = 600;

    /** The self-rating bands offered at registration (A30), with their start ratings. "unknown" and "newbie" use the admin's start value. */
    public const array STRENGTH_BANDS = [
        'unknown' => 0,
        'newbie' => 0,
        'casual' => 900,
        'club' => 1300,
        'strong_club' => 1700,
        'expert' => 2000,
    ];

    public function __construct(
        private readonly RatingRepository $ratings,
        private readonly SettingsRepository $settings,
    ) {
    }

    // --- After a game ---

    /** Applies the result of a finished game to both players' ratings, once (K4). */
    public function applyGameResult(Game $game, string $result, int $now): void
    {
        if (!$game->isRated || $result === Game::RESULT_ABORTED) {
            return;
        }
        $whiteScore = match ($result) {
            Game::RESULT_WHITE_WON => 1.0,
            Game::RESULT_BLACK_WON => 0.0,
            default => 0.5,
        };

        // Both rows locked in a fixed order (lower id first), so two games
        // ending at once can't wait on each other.
        $playerIds = [$game->whiteId, $game->blackId];
        sort($playerIds);
        $before = [];
        foreach ($playerIds as $playerId) {
            $this->ratings->createIfMissing($playerId, $game->speed, $this->startValue());
            $before[$playerId] = $this->ratings->find($playerId, $game->speed, true) ?? ['rating' => $this->startValue(), 'games_played' => 0];
        }

        $scores = [$game->whiteId => $whiteScore, $game->blackId => 1 - $whiteScore];
        foreach ($scores as $playerId => $score) {
            $opponentId = $game->opponentOf($playerId);
            $newRating = EloCalculator::newRating(
                $before[$playerId]['rating'],
                $before[$opponentId]['rating'],
                $score,
                $before[$playerId]['games_played'],
            );
            $isFirstTime = $this->ratings->addHistory($game->id, $playerId, $game->speed, $before[$playerId]['rating'], $newRating, $now);
            if ($isFirstTime) {
                $this->ratings->saveAfterGame($playerId, $game->speed, $newRating, $score);
            }
        }
    }

    // --- Start values ---

    /** The admin's start value for new players (A30). */
    public function startValue(): int
    {
        return $this->settings->getInt('rating.start_value', self::DEFAULT_START_VALUE);
    }

    /**
     * Sets a new player's start rating at every speed from the band chosen at registration (A30).
     *
     * @param list<string> $speeds
     */
    public function setStartFromBand(int $userId, string $band, array $speeds = [Game::SPEED_CORRESPONDENCE]): void
    {
        $bandValue = self::STRENGTH_BANDS[$band] ?? 0;
        $startRating = $bandValue > 0 ? $bandValue : $this->startValue();
        foreach ($speeds as $speed) {
            $this->ratings->createIfMissing($userId, $speed, $startRating);
        }
    }

    // --- Showing a rating ---

    /**
     * What to show for a player's rating at a speed: the number, "provisional"
     * (shown with a "?"), or nothing for a newbie (fewer than 3 games, A30).
     *
     * @return array{rating: int, games_played: int, is_newbie: bool, is_provisional: bool}
     */
    public function shownRating(int $userId, string $speed): array
    {
        $row = $this->ratings->find($userId, $speed);
        $rating = $row['rating'] ?? $this->startValue();
        $gamesPlayed = $row['games_played'] ?? 0;

        return [
            'rating' => $rating,
            'games_played' => $gamesPlayed,
            'is_newbie' => $gamesPlayed < EloCalculator::NEWBIE_GAMES,
            'is_provisional' => $gamesPlayed < EloCalculator::PROVISIONAL_GAMES,
        ];
    }
}
