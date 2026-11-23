<?php

declare(strict_types=1);

namespace PhpChess\Http\Web\Play;

use PhpChess\Domain\Play\Game;
use PhpChess\Domain\Rating\EloCalculator;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Kernel\Services;

/**
 * The rating list and a player's rating history (R-03, R-04).
 *
 * Addresses (Routes.php), members only, switch "player list visibility":
 *   GET /ratings              the rating list of the players with at least 3 games (A30)
 *   GET /players/{id}/ratings one player's rating, statistics and history chart
 */
final class RatingController
{
    public function __construct(
        private readonly Services $services,
        private readonly PlayPages $pages,
    ) {
    }

    public function list(Request $request): Response
    {
        $speed = Game::SPEED_CORRESPONDENCE;

        return $this->services->pages()->render($request, 'play/ratings', $this->pages->text('ratings.title'), [
            'speed' => $speed,
            'players' => $this->services->play()->ratingRepository()->ratingList($speed, EloCalculator::NEWBIE_GAMES),
        ]);
    }

    /**
     * @param array<string, string> $parts
     */
    public function history(Request $request, array $parts): Response
    {
        $player = $this->services->users()->findById(PlayPages::idFrom($parts));
        if ($player === null || !$player->isActive()) {
            return $this->pages->notFound($request);
        }
        $speed = Game::SPEED_CORRESPONDENCE;
        $repository = $this->services->play()->ratingRepository();

        return $this->services->pages()->render($request, 'play/rating_history', $this->pages->text('ratings.history_title', ['name' => $player->username]), [
            'player' => $player,
            'speed' => $speed,
            'shown' => $this->services->play()->ratings()->shownRating($player->id, $speed),
            'statistics' => $repository->find($player->id, $speed),
            'history' => $repository->history($player->id, $speed),
        ]);
    }
}
