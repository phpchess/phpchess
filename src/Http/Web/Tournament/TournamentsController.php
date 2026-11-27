<?php

declare(strict_types=1);

namespace PhpChess\Http\Web\Tournament;

use PhpChess\Domain\ActionRefused;
use PhpChess\Domain\Chess\Pgn;
use PhpChess\Domain\Tournament\IcsCalendar;
use PhpChess\Domain\Tournament\Tournament;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Http\Web\Play\PlayPages;
use PhpChess\Kernel\Services;

/**
 * The tournament pages for players and watchers (T-01, T-06 to T-08, A106 to A108).
 *
 *   GET  /tournaments                         the list: my tournaments, open for joining, running, finished
 *   GET  /tournaments/{id}                    one tournament; ?view= overview, standings, rounds, table, calendar, log
 *   GET  /tournaments/{id}/calendar.ics       the calendar for phones and calendar programs (A117)
 *   POST /tournaments/{id}/check-in           check in for the next round (A117)
 *   GET  /tournaments/{id}/pgn                every finished game as one PGN file (T-07)
 *   GET  /tournaments/invite/{token}          the invitation page of an invite-only tournament
 *   POST /tournaments/{id}/join               join (open, or with the invitation token)
 *   POST /tournaments/{id}/leave              leave before the start
 *   POST /tournaments/{id}/absence            "I will be away in round N" (or back again)
 *   POST /tournaments/{id}/withdraw           leave a running tournament
 *   POST /tournaments/{id}/boards/{board}/dispute   ask the organiser to look at a game
 *
 * Every page first calls TournamentRounds::tick(), so a round that is due
 * moves on when someone looks, without waiting for the job runner (A104).
 * Closed tournaments answer 404 to members who are not part of them (A106).
 */
final class TournamentsController
{
    public function __construct(
        private readonly Services $services,
        private readonly TournamentPages $pages,
    ) {
    }

    // --- The list ---

    public function list(Request $request): Response
    {
        $user = $this->pages->user();
        $repository = $this->services->tournaments()->repository();
        $access = $this->services->tournaments()->access();
        $mine = [];
        foreach ($repository->tournamentIdsOf($user->id) as $tournamentId) {
            $tournament = $repository->find($tournamentId);
            if ($tournament !== null) {
                $mine[$tournament->id] = $tournament;
            }
        }
        $visible = fn (Tournament $tournament): bool => !isset($mine[$tournament->id]) && $access->maySee($tournament, $user);

        return $this->pages->render($request, 'tournaments/list', $this->pages->text('tournament.list_title'), [
            'mine' => array_values($mine),
            'open' => array_values(array_filter($repository->listByStatus([Tournament::STATUS_REGISTRATION]), $visible)),
            'running' => array_values(array_filter($repository->listByStatus([Tournament::STATUS_RUNNING]), $visible)),
            'finished' => array_values(array_filter($repository->listByStatus([Tournament::STATUS_FINISHED], 30), $visible)),
            'mayCreate' => $access->mayCreate($user),
        ]);
    }

    // --- One tournament ---

    /**
     * @param array<string, string> $parts
     */
    public function show(Request $request, array $parts): Response
    {
        $tournament = $this->pages->visibleTournament($parts);
        if ($tournament === null) {
            return $this->pages->notFound($request);
        }
        if ($tournament->isRunning()) {
            $this->services->tournaments()->rounds()->tick($tournament->id, $this->services->now);
            $tournament = $this->services->tournaments()->repository()->find($tournament->id) ?? $tournament;
        }
        $user = $this->pages->user();
        $view = $request->queryValue('view', 'overview');
        if (!in_array($view, ['overview', 'standings', 'rounds', 'table', 'calendar', 'log'], true)) {
            $view = 'overview';
        }
        $data = (new TournamentView($this->services))->of($tournament, $user->id);

        return $this->pages->render($request, 'tournaments/show', $tournament->name, [
            'tournament' => $tournament,
            'view' => $view,
            'data' => $data,
            'viewerId' => $user->id,
            'isManager' => $this->services->tournaments()->access()->mayManage($tournament, $user),
            'inviteToken' => '',
        ] + $this->calendarParts($tournament, count($this->services->tournaments()->repository()->players($tournament->id)), $user->id));
    }

    /**
     * The page an invitation link opens: the tournament with a "Join" button.
     *
     * @param array<string, string> $parts
     */
    public function invitation(Request $request, array $parts): Response
    {
        $token = $parts['token'] ?? '';
        $tournament = preg_match('/^[a-f0-9]{32}$/', $token) === 1 ? $this->services->tournaments()->repository()->findByInviteToken($token) : null;
        if ($tournament === null) {
            return $this->pages->notFound($request);
        }
        $user = $this->pages->user();
        $data = (new TournamentView($this->services))->of($tournament, $user->id);

        return $this->pages->render($request, 'tournaments/show', $tournament->name, [
            'tournament' => $tournament,
            'view' => 'overview',
            'data' => $data,
            'viewerId' => $user->id,
            'isManager' => $this->services->tournaments()->access()->mayManage($tournament, $user),
            'inviteToken' => $token,
        ] + $this->calendarParts($tournament, count($this->services->tournaments()->repository()->players($tournament->id)), $user->id));
    }

    /**
     * The calendar (A114) and the check-in window (A117) for the tournament page.
     *
     * @return array{calendarRounds: list<array{round: int, start: int, deadline: int, isActual: bool}>, checkIn: array{round: int, closesAt: int, isCheckedIn: bool}|null}
     */
    private function calendarParts(Tournament $tournament, int $playerCount, int $viewerId): array
    {
        $rounds = $this->services->tournaments()->rounds();
        $window = $rounds->checkInWindow($tournament, $this->services->now);
        $checkIn = null;
        if ($window !== null) {
            $checkedIn = $this->services->tournaments()->repository()->checkedIn($tournament->id, $window['round']);
            $checkIn = $window + ['isCheckedIn' => in_array($viewerId, $checkedIn, true)];
        }

        return [
            // Before the start the players so far decide the round count (at least 2).
            'calendarRounds' => $rounds->calendarOf($tournament, max(2, $playerCount)),
            'checkIn' => $checkIn,
        ];
    }

    /**
     * The calendar as an .ics file for phones and calendar programs (A117).
     *
     * @param array<string, string> $parts
     */
    public function calendarFile(Request $request, array $parts): Response
    {
        $tournament = $this->pages->visibleTournament($parts);
        if ($tournament === null) {
            return $this->pages->notFound($request);
        }
        $playerCount = count($this->services->tournaments()->repository()->players($tournament->id));
        $rounds = $this->services->tournaments()->rounds()->calendarOf($tournament, $playerCount);
        $url = $request->baseUrl() . '/tournaments/' . $tournament->id;
        $body = IcsCalendar::of($tournament->name, $tournament->id, $rounds, $url, $request->host, $this->services->now);

        return new Response(200, $body, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="tournament-' . $tournament->id . '.ics"',
        ]);
    }

    /**
     * A player checks in for the next round (A117).
     *
     * @param array<string, string> $parts
     */
    public function checkIn(Request $request, array $parts): Response
    {
        $tournament = $this->pages->visibleTournament($parts);
        if ($tournament === null) {
            return $this->pages->notFound($request);
        }
        $round = (int) $request->formValue('round');

        return $this->pages->run($request, '/tournaments/' . $tournament->id, 'tournament.checked_in_done', function () use ($tournament, $round): void {
            $window = $this->services->tournaments()->rounds()->checkInWindow($tournament, $this->services->now);
            if ($window === null || $window['round'] !== $round) {
                throw new ActionRefused('tournament.check_in_closed');
            }
            $this->services->tournaments()->tournaments()->checkIn($tournament, $this->pages->user(), $round, $this->services->now);
        });
    }

    /**
     * Every finished game of the tournament as one PGN file (T-07).
     *
     * @param array<string, string> $parts
     */
    public function pgn(Request $request, array $parts): Response
    {
        $tournament = $this->pages->visibleTournament($parts);
        if ($tournament === null) {
            return $this->pages->notFound($request);
        }
        $games = $this->services->play()->gameRepository();
        $pairings = $this->services->tournaments()->repository()->pairings($tournament->id);
        $playerIds = [];
        foreach ($pairings as $pairing) {
            $playerIds[] = $pairing->whiteId;
            if ($pairing->blackId !== null) {
                $playerIds[] = $pairing->blackId;
            }
        }
        $names = $this->services->users()->usernamesOf(array_values(array_unique($playerIds)));
        $texts = [];
        foreach ($pairings as $pairing) {
            if ($pairing->gameId === null || !$pairing->counts()) {
                continue;
            }
            $game = $games->find($pairing->gameId);
            if ($game === null || $game->isRunning()) {
                continue;
            }
            $sanMoves = array_map(fn (array $move): string => $move['san'], $games->moves($game->id));
            $texts[] = Pgn::write([
                'Event' => $tournament->name,
                'Site' => $request->baseUrl() . '/tournaments/' . $tournament->id,
                'Date' => gmdate('Y.m.d', $game->startedAt),
                'Round' => $pairing->round . '.' . $pairing->board . ($pairing->gameNumber > 1 ? '.' . $pairing->gameNumber : ''),
                'White' => $names[$game->whiteId] ?? '?',
                'Black' => $names[$game->blackId] ?? '?',
                'Result' => $game->pgnResult(),
            ], $game->startFen, $sanMoves);
        }

        return new Response(200, implode("\n", $texts), [
            'Content-Type' => 'application/x-chess-pgn; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="tournament-' . $tournament->id . '.pgn"',
        ]);
    }

    // --- Buttons for players ---

    /**
     * @param array<string, string> $parts
     */
    public function join(Request $request, array $parts): Response
    {
        $tournament = $this->services->tournaments()->repository()->find(PlayPages::idFrom($parts));
        if ($tournament === null) {
            return $this->pages->notFound($request);
        }
        $token = $request->formValue('token');
        if ($tournament->isClosed() && $token === '' && !$this->services->tournaments()->access()->maySee($tournament, $this->pages->user())) {
            return $this->pages->notFound($request);
        }

        return $this->pages->run($request, '/tournaments/' . $tournament->id, 'tournament.joined', function () use ($tournament, $token): void {
            $this->services->tournaments()->tournaments()->join($tournament, $this->pages->user(), $this->services->now, $token);
        });
    }

    /**
     * @param array<string, string> $parts
     */
    public function leave(Request $request, array $parts): Response
    {
        $tournament = $this->pages->visibleTournament($parts);
        if ($tournament === null) {
            return $this->pages->notFound($request);
        }

        return $this->pages->run($request, '/tournaments/' . $tournament->id, 'tournament.left', function () use ($tournament): void {
            $this->services->tournaments()->tournaments()->leave($tournament, $this->pages->user(), $this->services->now);
        });
    }

    /**
     * @param array<string, string> $parts
     */
    public function absence(Request $request, array $parts): Response
    {
        $tournament = $this->pages->visibleTournament($parts);
        if ($tournament === null) {
            return $this->pages->notFound($request);
        }
        $user = $this->pages->user();
        $userId = (int) $request->formValue('user_id', (string) $user->id);
        $round = (int) $request->formValue('round');
        $isAway = $request->formValue('away') === 'yes';
        $back = $request->formValue('back') === 'manage' ? '/tournaments/' . $tournament->id . '/manage' : '/tournaments/' . $tournament->id;

        return $this->pages->run($request, $back, $isAway ? 'tournament.absence_saved' : 'tournament.absence_removed', function () use ($tournament, $user, $userId, $round, $isAway): void {
            $this->services->tournaments()->desk()->setAbsent($tournament, $user, $userId, $round, $isAway, $this->services->now);
        });
    }

    /**
     * @param array<string, string> $parts
     */
    public function withdraw(Request $request, array $parts): Response
    {
        $tournament = $this->pages->visibleTournament($parts);
        if ($tournament === null) {
            return $this->pages->notFound($request);
        }
        $user = $this->pages->user();

        return $this->pages->run($request, '/tournaments/' . $tournament->id, 'tournament.withdrawn', function () use ($tournament, $user): void {
            $this->services->tournaments()->desk()->withdraw($tournament, $user, $user->id, $this->services->now);
        });
    }

    /**
     * @param array<string, string> $parts
     */
    public function dispute(Request $request, array $parts): Response
    {
        $tournament = $this->pages->visibleTournament($parts);
        if ($tournament === null) {
            return $this->pages->notFound($request);
        }
        $pairingId = PlayPages::idFrom($parts, 'board');

        return $this->pages->run($request, '/tournaments/' . $tournament->id . '?view=rounds', 'tournament.dispute_sent', function () use ($tournament, $pairingId, $request): void {
            $this->services->tournaments()->desk()->raiseDispute($tournament, $this->pages->user(), $pairingId, $request->formValue('reason'), $this->services->now);
        });
    }
}
