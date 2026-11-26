<?php

declare(strict_types=1);

namespace PhpChess\Http\Web;

use LogicException;
use PhpChess\Domain\Identity\User;
use PhpChess\Domain\Puzzles\PuzzleSet;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Kernel\Services;

/**
 * The puzzle trainer (C5-1, L-01, A76): the bundled Lichess puzzles on our
 * own board. No outside request: the puzzles ship with phpChess.
 *
 * Addresses (Routes.php), members only:
 *   GET  /learn/puzzles        the page: choose a motif and a level, then solve
 *   POST /learn/puzzles/next   motif, level; answers {puzzle} or {error}
 *
 * The browser checks the moves itself (puzzles.js): a puzzle is practice,
 * so there is nothing to win by cheating. The last puzzles shown are kept
 * in the session so the same one doesn't come back at once.
 */
final class PuzzlesController
{
    private const string RECENT_KEY = 'puzzles.recent';

    private const int RECENT_TO_REMEMBER = 50;

    public function __construct(private readonly Services $services)
    {
    }

    public function show(Request $request): Response
    {
        $user = $this->currentUser();

        return $this->services->pages()->render($request, 'puzzles/trainer', $this->services->translator()->text('puzzles.title'), [
            'motifs' => PuzzleSet::MOTIFS,
            'levels' => array_keys(PuzzleSet::LEVELS),
            'boardTheme' => $this->services->preferenceRepository()->findForUser($user->id)->boardTheme,
            'puzzleCount' => $this->services->puzzleSet()->count(),
            // A plain link, not a request: it works with the switch off too (A75).
            'lichessTrainingLink' => 'https://lichess.org/training/',
        ]);
    }

    public function next(Request $request): Response
    {
        $this->currentUser();
        $motif = $request->formValue('motif', 'any');
        $level = $request->formValue('level', 'improving');
        if (!in_array($motif, PuzzleSet::MOTIFS, true) || !isset(PuzzleSet::LEVELS[$level])) {
            return Response::json(['error' => $this->services->translator()->text('puzzles.none')], 400);
        }

        $recent = $this->services->session->get(self::RECENT_KEY);
        $recentIds = is_array($recent) ? array_values(array_filter($recent, 'is_string')) : [];

        $puzzle = $this->services->puzzleSet()->next($motif, $level, $recentIds);
        if ($puzzle === null) {
            return Response::json(['error' => $this->services->translator()->text('puzzles.none')], 404);
        }

        $recentIds[] = $puzzle['id'];
        $this->services->session->set(self::RECENT_KEY, array_slice($recentIds, -self::RECENT_TO_REMEMBER));

        return Response::json(['puzzle' => [
            'id' => $puzzle['id'],
            'fen' => $puzzle['fen'],
            'moves' => $puzzle['moves'],
            'rating' => $puzzle['rating'],
        ]]);
    }

    /** The logged-in user; the routes are for members only, so there always is one. */
    private function currentUser(): User
    {
        $user = $this->services->authentication()->currentUser();
        if ($user === null) {
            throw new LogicException('A members-only route ran without a logged-in user.');
        }

        return $user;
    }
}
