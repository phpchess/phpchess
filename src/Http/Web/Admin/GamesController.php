<?php

declare(strict_types=1);

namespace PhpChess\Http\Web\Admin;

use PhpChess\Domain\Identity\Preferences;
use PhpChess\Domain\Notify\MoveEmails;
use PhpChess\Domain\Play\Clocks;
use PhpChess\Domain\Play\Game;
use PhpChess\Domain\Play\Games;
use PhpChess\Domain\Play\PlayRefused;
use PhpChess\Domain\Rating\Ratings;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Kernel\Services;

/**
 * The admin's games pages and play settings (ADM-04, A28, A30, A33 to A35, A37).
 *
 *   GET  /admin/games                   running and recently finished games
 *   POST /admin/games/{id}/adjudicate   set a result with a reason from the list (A37),
 *                                       through the game-end function (K3), audit-logged
 *   POST /admin/games/new               set up a game between two players from a FEN
 *   GET/POST /admin/play                the play settings: days per move, rating start
 *                                       value, abort rule, challenge expiry, move emails,
 *                                       start positions, adjudication reasons
 */
final class GamesController
{
    public const string DEFAULT_ADJUDICATION_REASONS = 'player_left,fair_play,technical_problem,agreed_by_players,other';

    public function __construct(
        private readonly Services $services,
        private readonly AdminPages $adminPages,
    ) {
    }

    // --- Games ---

    public function list(Request $request): Response
    {
        $games = $this->services->play()->gameRepository();
        $running = $games->listAll(Game::STATE_RUNNING, 200);
        $finished = $games->listAll(Game::STATE_FINISHED, 50);
        $ids = [];
        foreach ([...$running, ...$finished] as $game) {
            $ids[] = $game->whiteId;
            $ids[] = $game->blackId;
        }

        return $this->adminPages->render($request, 'admin/games', 'admin.games.title', [
            'runningGames' => $running,
            'finishedGames' => $finished,
            'usernames' => $this->services->users()->usernamesOf($ids),
            'reasons' => $this->adjudicationReasons(),
            'players' => $this->services->users()->activeUsernames(),
            'maximumDays' => $this->services->play()->challenges()->maximumDays(),
        ]);
    }

    /**
     * @param array<string, string> $parts
     */
    public function adjudicate(Request $request, array $parts): Response
    {
        $gameId = (int) ($parts['id'] ?? 0);
        $result = $request->formValue('result');
        $reason = $request->formValue('reason');
        if (!in_array($reason, $this->adjudicationReasons(), true)) {
            return $this->adminPages->redirect($request, '/admin/games', 'error', 'admin.games.reason_needed');
        }
        try {
            $hasEnded = $this->services->play()->games()->adjudicate($gameId, $result, $reason, $this->services->now);
        } catch (PlayRefused) {
            return $this->adminPages->redirect($request, '/admin/games', 'error', 'play.result_invalid');
        }
        if (!$hasEnded) {
            return $this->adminPages->redirect($request, '/admin/games', 'error', 'play.game_over');
        }
        $this->adminPages->audit($request, 'game.adjudicate', "Game {$gameId}: {$result}, reason {$reason}.");

        return $this->adminPages->redirect($request, '/admin/games', 'success', 'admin.games.adjudicated');
    }

    /** Starts a game between two players, from a FEN or the normal start (ADM-04). */
    public function create(Request $request): Response
    {
        $whiteId = (int) $request->formValue('white_id');
        $blackId = (int) $request->formValue('black_id');
        $players = $this->services->users()->activeUsernames();
        if (!isset($players[$whiteId], $players[$blackId])) {
            return $this->adminPages->redirect($request, '/admin/games', 'error', 'challenge.opponent_unknown');
        }
        $daysPerMove = Clocks::limitDays((int) $request->formValue('days_per_move', '3'), $this->services->play()->challenges()->maximumDays());
        try {
            $gameId = $this->services->play()->games()->start(
                $whiteId,
                $blackId,
                $daysPerMove,
                $request->formValue('rated') === 'yes',
                trim($request->formValue('start_fen')),
                $this->services->now,
            );
        } catch (PlayRefused $refusal) {
            $this->services->pages()->addFlashMessage('error', $this->adminPages->text('play.fen_invalid_short') . ' ' . ($refusal->values['problem'] ?? ''));

            return Response::redirect($request->basePath . '/admin/games');
        }
        $this->adminPages->audit($request, 'game.create', "Game {$gameId} set up: {$players[$whiteId]} - {$players[$blackId]}.");

        return Response::redirect($request->basePath . '/games/' . $gameId);
    }

    // --- Play settings ---

    public function showSettings(Request $request): Response
    {
        $settings = $this->services->settings();

        return $this->adminPages->render($request, 'admin/play', 'admin.play.title', [
            'maxDaysPerMove' => $this->services->play()->challenges()->maximumDays(),
            'ratingStart' => $this->services->play()->ratings()->startValue(),
            'abortRule' => $this->services->play()->games()->abortRule(),
            'openDays' => $settings->getInt('challenge.open_days', 7),
            'directDays' => $settings->getInt('challenge.direct_days', 14),
            'inviteDays' => $settings->getInt('challenge.invite_days', 7),
            'fenInDirect' => $settings->getBool('play.fen_in_direct', true),
            'planningBoardTheme' => $settings->getString('play.planning_board_theme', 'blue'),
            'allowedEmailModes' => $this->services->moveEmails()->allowedModes(),
            'defaultEmailMode' => $this->services->moveEmails()->defaultMode(),
            'emailDelayMinutes' => $settings->getInt('mail.move_email_delay_minutes', 60),
            'adjudicationReasons' => implode(', ', $this->adjudicationReasons()),
        ]);
    }

    public function saveSettings(Request $request): Response
    {
        $settings = $this->services->settings();
        $settings->set('play.max_days_per_move', Clocks::limitDays((int) $request->formValue('max_days_per_move', '30'), Clocks::HIGHEST_ALLOWED_DAYS));
        $settings->set('rating.start_value', max(100, min(3000, (int) $request->formValue('rating_start', (string) Ratings::DEFAULT_START_VALUE))));
        $abortRule = $request->formValue('abort_rule');
        $settings->set('play.abort_rule', in_array($abortRule, Games::ABORT_RULES, true) ? $abortRule : Games::ABORT_ALLOWED);
        foreach (['open_days' => 7, 'direct_days' => 14, 'invite_days' => 7] as $name => $default) {
            $settings->set('challenge.' . $name, max(1, min(365, (int) $request->formValue($name, (string) $default))));
        }
        $settings->set('play.fen_in_direct', $request->formValue('fen_in_direct') === 'yes');
        $planningTheme = $request->formValue('planning_board_theme');
        $settings->set('play.planning_board_theme', in_array($planningTheme, Preferences::BOARD_THEMES, true) ? $planningTheme : 'blue');

        $allowed = [];
        foreach (MoveEmails::MODES as $mode) {
            if ($request->formValue('email_mode_' . $mode) === 'yes') {
                $allowed[] = $mode;
            }
        }
        $settings->set('mail.move_email_allowed', implode(',', $allowed === [] ? [MoveEmails::OFF] : $allowed));
        $defaultMode = $request->formValue('default_email_mode');
        $settings->set('mail.move_email_default', in_array($defaultMode, MoveEmails::MODES, true) ? $defaultMode : MoveEmails::DELAY);
        $settings->set('mail.move_email_delay_minutes', max(1, min(1440, (int) $request->formValue('email_delay_minutes', '60'))));

        $reasons = [];
        foreach (explode(',', $request->formValue('adjudication_reasons')) as $reason) {
            $reason = strtolower(trim($reason));
            if (preg_match('/^[a-z][a-z0-9_]{0,24}$/', $reason) === 1) {
                $reasons[] = $reason;
            }
        }
        $settings->set('adjudication.reasons', implode(',', $reasons === [] ? ['other'] : array_unique($reasons)));

        $this->adminPages->audit($request, 'settings.play', 'Play settings saved.');

        return $this->adminPages->redirect($request, '/admin/play', 'success', 'admin.saved');
    }

    /**
     * The admin's list of adjudication reasons (A37), as short keys.
     *
     * @return list<string>
     */
    private function adjudicationReasons(): array
    {
        $text = $this->services->settings()->getString('adjudication.reasons', self::DEFAULT_ADJUDICATION_REASONS);

        return array_values(array_filter(explode(',', $text), fn (string $reason): bool => $reason !== ''));
    }
}
