<?php

declare(strict_types=1);

namespace PhpChess\Http\Web;

use LogicException;
use PhpChess\Domain\Identity\User;
use PhpChess\Domain\Play\Game;
use PhpChess\Domain\Strength\StrengthTest;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Kernel\Services;

/**
 * The strength test (B15, A59, A60): 10 puzzles that suggest a strength band.
 *
 * Addresses (Routes.php), members only:
 *   GET  /strength-test          the page: start, or the last result and when a new test is possible
 *   POST /strength-test/start    begins a test; answers {puzzle}
 *   POST /strength-test/answer   puzzle_id, solved (yes/no); answers {puzzle} or {done, band, rating}
 *
 * The answers so far live in the session; the puzzles are chosen by
 * StrengthTest (adaptive). The browser checks the moves itself: the test
 * is a self-assessment, so there is nothing to win by cheating.
 * At the end the band is stored and becomes the start rating at every
 * speed the player has not played yet (Ratings::setStartFromTest).
 */
final class StrengthTestController
{
    /** Session keys: the answers so far, and the puzzle on the board. */
    private const string ANSWERS_KEY = 'strength_test.answers';

    private const string CURRENT_KEY = 'strength_test.current';

    public function __construct(private readonly Services $services)
    {
    }

    // --- The page ---

    public function show(Request $request): Response
    {
        $user = $this->currentUser();
        $status = $this->services->preferenceRepository()->strengthTest($user->id);

        return $this->services->pages()->render($request, 'account/strength_test', $this->text('strength_test.title'), [
            'band' => $status['band'],
            'nextTestDate' => $this->nextTestDate($status['tested_at']),
            'boardTheme' => $this->services->preferenceRepository()->findForUser($user->id)->boardTheme,
        ]);
    }

    // --- Taking the test ---

    public function start(Request $request): Response
    {
        $user = $this->currentUser();
        $status = $this->services->preferenceRepository()->strengthTest($user->id);
        if ($this->nextTestDate($status['tested_at']) !== null) {
            return Response::json(['error' => $this->text('strength_test.too_soon')], 409);
        }

        $this->services->session->set(self::ANSWERS_KEY, []);

        return $this->showNext([]);
    }

    public function answer(Request $request): Response
    {
        $user = $this->currentUser();
        $answers = $this->answersSoFar();
        $puzzleId = $request->formValue('puzzle_id');

        // Only the puzzle that is on the board can be answered, once.
        if ($puzzleId === '' || $puzzleId !== $this->services->session->getString(self::CURRENT_KEY)) {
            return Response::json(['error' => $this->text('strength_test.lost')], 409);
        }
        $answers[] = ['id' => $puzzleId, 'solved' => $request->formValue('solved') === 'yes'];
        $this->services->session->set(self::ANSWERS_KEY, $answers);
        $this->services->session->remove(self::CURRENT_KEY);

        if (count($answers) < StrengthTest::PUZZLES_PER_TEST) {
            return $this->showNext($answers);
        }

        // The test is complete: store the band and use it as the start rating.
        $test = new StrengthTest();
        $rating = $test->estimatedRating($answers);
        $band = StrengthTest::bandFor($rating);
        $this->services->preferenceRepository()->saveStrengthTestResult($user->id, $band);
        $this->services->play()->ratings()->setStartFromTest($user->id, $band);
        $this->services->session->remove(self::ANSWERS_KEY);

        return Response::json([
            'done' => true,
            'band' => $this->text('register.strength.' . $band),
            'rating' => $rating,
            'solved' => count(array_filter(array_column($answers, 'solved'))),
        ]);
    }

    /**
     * Whether to offer the test next to a member's own rating: until their
     * strength is known, that is never tested and still a newbie (A60).
     */
    public static function shouldOffer(Services $services, int $userId): bool
    {
        if ($services->preferenceRepository()->strengthTest($userId)['tested_at'] !== null) {
            return false;
        }

        return $services->play()->ratings()->shownRating($userId, Game::SPEED_CORRESPONDENCE)['is_newbie'];
    }

    // --- Helpers ---

    /** @param list<array{id: string, solved: bool}> $answers */
    private function showNext(array $answers): Response
    {
        $puzzle = (new StrengthTest())->nextPuzzle($answers);
        if ($puzzle === null) {
            return Response::json(['error' => $this->text('strength_test.lost')], 500);
        }
        $this->services->session->set(self::CURRENT_KEY, $puzzle->id);

        return Response::json([
            'puzzle' => $puzzle->forBrowser(),
            'number' => count($answers) + 1,
            'total' => StrengthTest::PUZZLES_PER_TEST,
        ]);
    }

    /** @return list<array{id: string, solved: bool}> */
    private function answersSoFar(): array
    {
        $stored = $this->services->session->get(self::ANSWERS_KEY);
        $answers = [];
        foreach (is_array($stored) ? $stored : [] as $answer) {
            if (is_array($answer) && is_string($answer['id'] ?? null) && is_bool($answer['solved'] ?? null)) {
                $answers[] = ['id' => $answer['id'], 'solved' => $answer['solved']];
            }
        }

        return $answers;
    }

    /** The date a new test is possible ("2026-12-25"), or null when it is possible now. */
    private function nextTestDate(?string $testedAt): ?string
    {
        if ($testedAt === null) {
            return null;
        }
        $next = strtotime($testedAt . ' UTC') + StrengthTest::DAYS_BETWEEN_TESTS * 86400;

        return $next > time() ? gmdate('Y-m-d', $next) : null;
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

    /** @param array<string, string|int> $values */
    private function text(string $key, array $values = []): string
    {
        return $this->services->translator()->text($key, $values);
    }
}
