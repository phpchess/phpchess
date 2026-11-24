<?php

declare(strict_types=1);

namespace PhpChess\Http\Web\Play;

use PhpChess\Domain\Play\PlayRefused;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Kernel\Services;

/**
 * Playing the computer (AI-03, AI-06, decision A53):
 *
 *   GET  /play/computer   the computer players: face, name, rating, personality, bio
 *   POST /play/computer   starts an unrated game against one of them
 *
 * The computer's moves are sent by the game page to
 * POST /games/{id}/computer-move (GameController::computerMove).
 */
final class ComputerController
{
    /** Where the faces are, below the public folder (A53). */
    public const string FACES_FOLDER = '/assets/faces/computer/';

    public function __construct(
        private readonly Services $services,
        private readonly PlayPages $pages,
    ) {
    }

    /** The list of computer players to choose from. */
    public function list(Request $request): Response
    {
        $players = $this->services->play()->computerGames()->allPlayers();
        $facesOnDisk = $this->services->appRoot . '/public' . self::FACES_FOLDER;
        $faces = [];
        foreach ($players as $player) {
            $faces[$player->id] = is_file($facesOnDisk . $player->faceFile())
                ? $request->basePath . self::FACES_FOLDER . $player->faceFile()
                : null;
        }

        return $this->services->pages()->render($request, 'play/computer', $this->pages->text('computer.title'), [
            'players' => $players,
            'faces' => $faces,
        ]);
    }

    /** Starts a game against the chosen computer player and opens it. */
    public function start(Request $request): Response
    {
        $user = $this->pages->user();
        try {
            $gameId = $this->services->play()->computerGames()->start(
                $user->id,
                (int) $request->formValue('player', '-1'),
                $request->formValue('colour', 'random'),
                $this->services->now,
            );
        } catch (PlayRefused $refusal) {
            return $this->pages->refused($request, '/play/computer', $refusal);
        }

        return Response::redirect($request->basePath . '/games/' . $gameId);
    }
}
