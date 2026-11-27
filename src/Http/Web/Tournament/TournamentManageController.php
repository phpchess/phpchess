<?php

declare(strict_types=1);

namespace PhpChess\Http\Web\Tournament;

use PhpChess\Domain\ActionRefused;
use PhpChess\Domain\Tournament\PlayerQuery;
use PhpChess\Domain\Tournament\TieBreaks;
use PhpChess\Domain\Tournament\Tournament;
use PhpChess\Domain\Tournament\TournamentCalendar;
use PhpChess\Domain\Tournament\TournamentDesk;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Http\Web\Play\PlayPages;
use PhpChess\Kernel\Services;

/**
 * The organiser's pages (T-01 to T-08, ADM-06, A98, A103, A107 to A109).
 *
 *   GET/POST /tournaments/new                       create a tournament: name, format, pace; the rest has defaults
 *   GET  /tournaments/{id}/manage                   the organiser's desk; ?view= players, rounds, disputes, settings
 *   POST /tournaments/{id}/settings                 change the settings (until the start)
 *   POST /tournaments/{id}/players/add              add a member by name
 *   POST /tournaments/{id}/players/add-found        add the members a player query found (A116)
 *   POST /tournaments/{id}/players/computer         add a computer player (A105)
 *   POST /tournaments/{id}/players/{user}/remove    take off the list (before the start)
 *   POST /tournaments/{id}/players/{user}/withdraw  withdraw a player (A108)
 *   POST /tournaments/{id}/players/{user}/replace   replace a player who has not played (A108)
 *   POST /tournaments/{id}/organisers/add | /organisers/{user}/remove   co-organisers (A98)
 *   POST /tournaments/{id}/invitation               a new invitation link
 *   POST /tournaments/{id}/guests                   guest logins for this tournament (A109)
 *   POST /tournaments/{id}/start                    start: seed and pair round 1
 *   POST /tournaments/{id}/round/start | /round/repair | /round/close   the current round
 *   POST /tournaments/{id}/boards/{board}/decide | /postpone | /start | /replay   one board (A108)
 *   POST /tournaments/{id}/disputes/{dispute}/answer   answer a dispute (A108)
 *   POST /tournaments/{id}/pause | /cancel
 *   GET/POST /tournaments/{id}/league               the League editor (A103); POST saves a placement
 *   POST /tournaments/{id}/league/divisions/add | /league/divisions/remove
 *   POST /tournaments/{id}/next-season              the next League season
 *
 * Only the tournament's organisers and admins get these pages; everyone
 * else gets 404 (K14). The services check the right again.
 */
final class TournamentManageController
{
    public function __construct(
        private readonly Services $services,
        private readonly TournamentPages $pages,
    ) {
    }

    // --- Creating ---

    public function showCreate(Request $request): Response
    {
        if (!$this->services->tournaments()->access()->mayCreate($this->pages->user())) {
            return $this->pages->notFound($request);
        }

        return $this->pages->render($request, 'tournaments/create', $this->pages->text('tournament.create_title'), [
            'timeControls' => $this->services->play()->games()->allowedTimeControls(),
            'maximumDays' => $this->services->play()->games()->maximumDays(),
            'values' => [],
            'calendarRounds' => [],
            'proposalMessage' => '',
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $this->pages->user();
        if ($request->formValue('action') === 'propose') {
            return $this->showProposal($request);
        }
        try {
            $tournamentId = $this->services->tournaments()->tournaments()->create($user, self::settingsFrom($request), $this->services->now);
        } catch (ActionRefused $refusal) {
            return $this->services->pages()->redirectWithMessage($request, '/tournaments/new', 'error', $this->pages->text($refusal->languageKey, $refusal->values));
        }

        return $this->services->pages()->redirectWithMessage($request, '/tournaments/' . $tournamentId . '/manage', 'success', $this->pages->text('tournament.created'));
    }

    /**
     * "Propose from dates" on the create page (A114): the form comes back
     * with round days and a break that fit between the start and finish
     * date, and the calendar they give. Nothing is saved yet.
     */
    private function showProposal(Request $request): Response
    {
        $values = $request->form;
        $values['rest_weekdays'] = self::settingsFrom($request)['rest_weekdays'];
        $message = '';
        $calendarRounds = [];
        try {
            $settings = $this->services->tournaments()->tournaments()->checkedSettings(self::settingsFrom($request) + ['name' => 'x']);
            $preview = Tournament::preview($settings);
            $players = max(2, (int) $request->formValue('expected_players', '8'));
            $finish = strtotime($request->formValue('finish_date') . ' 23:59');
            $start = $preview->startsAt;
            $rounds = TournamentCalendar::roundCount($preview, $players);
            $proposal = ($start === null || $finish === false) ? null : $preview->calendar()->propose($start, $finish, $rounds);
            if ($proposal === null) {
                $message = $this->pages->text('tournament.propose_impossible', ['rounds' => $rounds]);
            } else {
                $values['round_days'] = (string) $proposal['roundDays'];
                $values['round_gap_days'] = (string) $proposal['gapDays'];
                $calendarRounds = $preview->calendar()->plan((int) $start, $rounds, $proposal['roundDays'], $proposal['gapDays']);
                $message = $this->pages->text('tournament.proposed', ['rounds' => $rounds, 'days' => $proposal['roundDays'], 'gap' => $proposal['gapDays']]);
            }
        } catch (ActionRefused $refusal) {
            $message = $this->pages->text($refusal->languageKey, $refusal->values);
        }

        return $this->pages->render($request, 'tournaments/create', $this->pages->text('tournament.create_title'), [
            'timeControls' => $this->services->play()->games()->allowedTimeControls(),
            'maximumDays' => $this->services->play()->games()->maximumDays(),
            'values' => $values,
            'calendarRounds' => $calendarRounds,
            'proposalMessage' => $message,
        ]);
    }

    /**
     * "Propose from dates" on the settings view (A114): works out round days
     * and a break that fit, saves them with the other settings, and says so.
     *
     * @param array<string, string> $parts
     */
    private function proposeForTournament(Request $request, array $parts): Response
    {
        $tournament = $this->pages->managedTournament($parts);
        if ($tournament === null) {
            return $this->pages->notFound($request);
        }
        $back = '/tournaments/' . $tournament->id . '/manage?view=settings';
        $players = max(2, count($this->services->tournaments()->repository()->players($tournament->id)), (int) $request->formValue('expected_players', '2'));
        $finish = strtotime($request->formValue('finish_date') . ' 23:59');
        $settings = self::settingsFrom($request);
        try {
            $preview = Tournament::preview($this->services->tournaments()->tournaments()->checkedSettings($settings + ['format' => $tournament->format]));
        } catch (ActionRefused $refusal) {
            return $this->services->pages()->redirectWithMessage($request, $back, 'error', $this->pages->text($refusal->languageKey, $refusal->values));
        }
        $rounds = TournamentCalendar::roundCount($preview, $players);
        $start = $tournament->isOpenForRegistration() ? $preview->startsAt : $this->services->now;
        $proposal = ($start === null || $finish === false) ? null : $preview->calendar()->propose($start, $finish, $rounds);
        if ($proposal === null) {
            return $this->services->pages()->redirectWithMessage($request, $back, 'error', $this->pages->text('tournament.propose_impossible', ['rounds' => $rounds]));
        }
        $settings['round_days'] = (string) $proposal['roundDays'];
        $settings['round_gap_days'] = (string) $proposal['gapDays'];

        return $this->pages->run($request, $back, 'tournament.saved', function () use ($tournament, $settings): void {
            $this->services->tournaments()->tournaments()->updateSettings($tournament, $this->pages->user(), $settings, $this->services->now);
        });
    }

    /**
     * The settings fields of the create and settings forms.
     *
     * @return array<string, string>
     */
    private static function settingsFrom(Request $request): array
    {
        $settings = [];
        foreach ([
            'name', 'description', 'format', 'registration', 'max_players', 'pace', 'days_per_move', 'time_control',
            'round_days', 'rounds_planned', 'double_round', 'knockout_games', 'third_place', 'is_rated', 'allow_computers',
            'confirm_pairings', 'forfeit_rule', 'bye_halves', 'absence_bye_halves', 'tie_breaks', 'divisions',
            'promote_count', 'relegate_count', 'starts_at', 'round_gap_days', 'blocked_dates', 'early_rounds',
            'forfeits_rated', 'armageddon_higher_black', 'withdrawal_removes', 'check_in_minutes',
        ] as $field) {
            $settings[$field] = $request->formValue($field);
        }
        // Checkboxes send nothing when off.
        $settings['is_rated'] = $request->formValue('is_rated', 'no');

        // Rest weekdays: one box per day, "rest_day_6" = Saturday (A114).
        $restDays = [];
        for ($weekday = 1; $weekday <= 7; $weekday++) {
            if ($request->formValue('rest_day_' . $weekday) === 'yes') {
                $restDays[] = $weekday;
            }
        }
        $settings['rest_weekdays'] = implode(',', $restDays);

        // A pace button ("days:3" or "live:10+5") wins over the fields under "More settings".
        $paceChoice = $request->formValue('pace_choice');
        if (str_starts_with($paceChoice, 'days:')) {
            $settings['pace'] = Tournament::PACE_CORRESPONDENCE;
            $settings['days_per_move'] = substr($paceChoice, 5);
        } elseif (str_starts_with($paceChoice, 'live:')) {
            $settings['pace'] = Tournament::PACE_TIMED;
            $settings['time_control'] = substr($paceChoice, 5);
        }

        return $settings;
    }

    // --- The desk ---

    /**
     * @param array<string, string> $parts
     */
    public function show(Request $request, array $parts): Response
    {
        $tournament = $this->pages->managedTournament($parts);
        if ($tournament === null) {
            return $this->pages->notFound($request);
        }
        if ($tournament->isRunning()) {
            $this->services->tournaments()->rounds()->tick($tournament->id, $this->services->now);
            $tournament = $this->services->tournaments()->repository()->find($tournament->id) ?? $tournament;
        }
        $view = $request->queryValue('view', 'players');
        if (!in_array($view, ['players', 'rounds', 'disputes', 'settings'], true)) {
            $view = 'players';
        }
        $repository = $this->services->tournaments()->repository();
        $data = (new TournamentView($this->services))->of($tournament, $this->pages->user()->id);
        $disputes = $repository->disputes($tournament->id);
        $raisedBy = array_map(fn (array $dispute): int => $dispute['raisedBy'], $disputes);

        return $this->pages->render($request, 'tournaments/manage', $tournament->name, [
            'tournament' => $tournament,
            'view' => $view,
            'data' => $data,
            'disputes' => $disputes,
            'disputeNames' => $this->services->users()->usernamesOf($raisedBy),
            'organiserNames' => $this->services->users()->usernamesOf([...$repository->organiserIds($tournament->id), $tournament->createdBy]),
            'computerPlayers' => $tournament->allowsComputers ? $this->services->play()->computerGames()->allPlayers() : [],
            'timeControls' => $this->services->play()->games()->allowedTimeControls(),
            'maximumDays' => $this->services->play()->games()->maximumDays(),
            'tieBreakKeys' => TieBreaks::ALL,
            'guestLogins' => [],
            'inviteUrl' => $tournament->inviteToken === null ? '' : $request->baseUrl() . '/tournaments/invite/' . $tournament->inviteToken,
            'settingsValues' => $tournament->settingsValues(),
            'calendarRounds' => $this->services->tournaments()->rounds()->calendarOf($tournament, max(2, count($this->services->tournaments()->repository()->players($tournament->id)))),
        ] + $this->queryParts($request, $tournament));
    }

    /**
     * The player query (A116) on the players view: the query box's values,
     * and what it found when the organiser pressed "Find" (?find=1).
     *
     * @return array{query: array<string, string>, queryFound: list<array{id: int, username: string, rating: int, games: int, joinedAt: int}>|null, classes: list<array{id: int, name: string}>}
     */
    private function queryParts(Request $request, Tournament $tournament): array
    {
        $query = PlayerQuery::fromInput($request->query);
        $found = null;
        if ($request->queryValue('find') === '1' && $tournament->isOpenForRegistration()) {
            $found = $this->services->tournaments()->tournaments()->findPlayers($tournament, $this->pages->user(), $query, $this->services->play()->ratings()->startValue(), $this->services->now);
        }
        $classes = array_map(fn (array $class): array => ['id' => $class['id'], 'name' => $class['name']], $this->services->school()->listClasses(null));

        return ['query' => $query->toInput(), 'queryFound' => $found, 'classes' => $classes];
    }

    // --- Settings and players ---

    /**
     * @param array<string, string> $parts
     */
    public function saveSettings(Request $request, array $parts): Response
    {
        if ($request->formValue('action') === 'propose') {
            return $this->proposeForTournament($request, $parts);
        }

        return $this->act($request, $parts, 'settings', 'tournament.saved', function (Tournament $tournament) use ($request): void {
            $this->services->tournaments()->tournaments()->updateSettings($tournament, $this->pages->user(), self::settingsFrom($request), $this->services->now);
        });
    }

    /**
     * @param array<string, string> $parts
     */
    public function addPlayer(Request $request, array $parts): Response
    {
        return $this->act($request, $parts, 'players', 'tournament.player_added', function (Tournament $tournament) use ($request): void {
            $this->services->tournaments()->tournaments()->addPlayerByName($tournament, $this->pages->user(), $request->formValue('username'), $this->services->now);
        });
    }

    /**
     * "Add all N" or "Add ticked" from the player query (A116). The query
     * runs again here, so only members it really finds are added; with
     * "ticked", only the ticked ones among them.
     *
     * @param array<string, string> $parts
     */
    public function addFoundPlayers(Request $request, array $parts): Response
    {
        $tournament = $this->pages->managedTournament($parts);
        if ($tournament === null) {
            return $this->pages->notFound($request);
        }
        $user = $this->pages->user();
        $query = PlayerQuery::fromInput($request->form);
        $tournaments = $this->services->tournaments()->tournaments();
        $found = array_map(fn (array $player): int => $player['id'], $tournaments->findPlayers($tournament, $user, $query, $this->services->play()->ratings()->startValue(), $this->services->now));
        if ($request->formValue('which') === 'ticked') {
            $ticked = [];
            foreach ($request->form as $field => $value) {
                if (str_starts_with((string) $field, 'pick_') && $value === 'yes') {
                    $ticked[] = (int) substr((string) $field, 5);
                }
            }
            $found = array_values(array_intersect($found, $ticked));
        }
        $result = $tournaments->addPlayers($tournament, $user, $found, $this->services->now);

        return $this->services->pages()->redirectWithMessage(
            $request,
            '/tournaments/' . $tournament->id . '/manage?view=players',
            $result['added'] > 0 ? 'success' : 'error',
            $this->pages->text('tournament.players_added_count', ['added' => $result['added'], 'skipped' => $result['skipped']]),
        );
    }

    /**
     * @param array<string, string> $parts
     */
    public function addComputer(Request $request, array $parts): Response
    {
        return $this->act($request, $parts, 'players', 'tournament.player_added', function (Tournament $tournament) use ($request): void {
            $this->services->tournaments()->tournaments()->addComputer($tournament, $this->pages->user(), (int) $request->formValue('computer_player_id'), $this->services->now);
        });
    }

    /**
     * @param array<string, string> $parts
     */
    public function removePlayer(Request $request, array $parts): Response
    {
        $userId = PlayPages::idFrom($parts, 'user');

        return $this->act($request, $parts, 'players', 'tournament.player_removed', function (Tournament $tournament) use ($userId): void {
            $this->services->tournaments()->tournaments()->removePlayer($tournament, $this->pages->user(), $userId, $this->services->now);
        });
    }

    /**
     * @param array<string, string> $parts
     */
    public function withdrawPlayer(Request $request, array $parts): Response
    {
        $userId = PlayPages::idFrom($parts, 'user');

        return $this->act($request, $parts, 'players', 'tournament.withdrawn', function (Tournament $tournament) use ($userId): void {
            $this->services->tournaments()->desk()->withdraw($tournament, $this->pages->user(), $userId, $this->services->now);
        });
    }

    /**
     * @param array<string, string> $parts
     */
    public function replacePlayer(Request $request, array $parts): Response
    {
        $userId = PlayPages::idFrom($parts, 'user');

        return $this->act($request, $parts, 'players', 'tournament.replaced', function (Tournament $tournament) use ($userId, $request): void {
            $this->services->tournaments()->desk()->replace($tournament, $this->pages->user(), $userId, $request->formValue('username'), $this->services->now);
        });
    }

    /**
     * @param array<string, string> $parts
     */
    public function addOrganiser(Request $request, array $parts): Response
    {
        return $this->act($request, $parts, 'settings', 'tournament.organiser_added', function (Tournament $tournament) use ($request): void {
            $this->services->tournaments()->tournaments()->addOrganiser($tournament, $this->pages->user(), $request->formValue('username'), $this->services->now);
        });
    }

    /**
     * @param array<string, string> $parts
     */
    public function removeOrganiser(Request $request, array $parts): Response
    {
        $userId = PlayPages::idFrom($parts, 'user');

        return $this->act($request, $parts, 'settings', 'tournament.organiser_removed', function (Tournament $tournament) use ($userId): void {
            $this->services->tournaments()->tournaments()->removeOrganiser($tournament, $this->pages->user(), $userId, $this->services->now);
        });
    }

    /**
     * @param array<string, string> $parts
     */
    public function renewInvitation(Request $request, array $parts): Response
    {
        return $this->act($request, $parts, 'players', 'tournament.invitation_renewed', function (Tournament $tournament): void {
            $this->services->tournaments()->tournaments()->renewInvitation($tournament, $this->pages->user(), $this->services->now);
        });
    }

    /**
     * Makes guest logins and shows them once, to print or hand out (A109).
     *
     * @param array<string, string> $parts
     */
    public function createGuests(Request $request, array $parts): Response
    {
        $tournament = $this->pages->managedTournament($parts);
        if ($tournament === null) {
            return $this->pages->notFound($request);
        }
        try {
            $logins = $this->services->tournaments()->tournaments()->createGuests(
                $tournament,
                $this->pages->user(),
                $request->formValue('prefix'),
                (int) $request->formValue('count', '1'),
                $this->services->now,
            );
        } catch (ActionRefused $refusal) {
            return $this->services->pages()->redirectWithMessage($request, '/tournaments/' . $tournament->id . '/manage', 'error', $this->pages->text($refusal->languageKey, $refusal->values));
        }

        return $this->pages->render($request, 'tournaments/guests', $this->pages->text('tournament.guests_title'), [
            'tournament' => $tournament,
            'logins' => $logins,
            'loginUrl' => $request->baseUrl() . '/login',
        ]);
    }

    // --- Rounds ---

    /**
     * @param array<string, string> $parts
     */
    public function start(Request $request, array $parts): Response
    {
        return $this->act($request, $parts, 'rounds', 'tournament.started', function (Tournament $tournament): void {
            $this->services->tournaments()->rounds()->start($tournament, $this->pages->user(), $this->services->now);
        });
    }

    /**
     * @param array<string, string> $parts
     */
    public function startRound(Request $request, array $parts): Response
    {
        return $this->act($request, $parts, 'rounds', 'tournament.round_started', function (Tournament $tournament): void {
            $this->services->tournaments()->rounds()->startPairedRound($tournament, $this->pages->user(), $this->services->now);
        });
    }

    /**
     * @param array<string, string> $parts
     */
    public function repairRound(Request $request, array $parts): Response
    {
        return $this->act($request, $parts, 'rounds', 'tournament.round_repaired', function (Tournament $tournament): void {
            $this->services->tournaments()->rounds()->repairRound($tournament, $this->pages->user(), $this->services->now);
        });
    }

    /**
     * @param array<string, string> $parts
     */
    public function closeRound(Request $request, array $parts): Response
    {
        return $this->act($request, $parts, 'rounds', 'tournament.round_closed', function (Tournament $tournament): void {
            $this->services->tournaments()->rounds()->closeRoundNow($tournament, $this->pages->user(), $this->services->now);
        });
    }

    // --- Boards ---

    /**
     * @param array<string, string> $parts
     */
    public function decideBoard(Request $request, array $parts): Response
    {
        $pairingId = PlayPages::idFrom($parts, 'board');

        return $this->act($request, $parts, 'rounds', 'tournament.board_decided', function (Tournament $tournament) use ($pairingId, $request): void {
            $this->services->tournaments()->desk()->decide($tournament, $this->pages->user(), $pairingId, $request->formValue('decision'), $this->services->now);
        });
    }

    /**
     * @param array<string, string> $parts
     */
    public function postponeBoard(Request $request, array $parts): Response
    {
        $pairingId = PlayPages::idFrom($parts, 'board');

        return $this->act($request, $parts, 'rounds', 'tournament.board_postponed', function (Tournament $tournament) use ($pairingId, $request): void {
            $this->services->tournaments()->desk()->postpone($tournament, $this->pages->user(), $pairingId, (int) $request->formValue('days', '3'), $this->services->now);
        });
    }

    /**
     * @param array<string, string> $parts
     */
    public function startBoard(Request $request, array $parts): Response
    {
        $pairingId = PlayPages::idFrom($parts, 'board');

        return $this->act($request, $parts, 'rounds', 'tournament.board_started', function (Tournament $tournament) use ($pairingId): void {
            $this->services->tournaments()->desk()->startNow($tournament, $this->pages->user(), $pairingId, $this->services->now);
        });
    }

    /**
     * @param array<string, string> $parts
     */
    public function replayBoard(Request $request, array $parts): Response
    {
        $pairingId = PlayPages::idFrom($parts, 'board');

        return $this->act($request, $parts, 'rounds', 'tournament.board_replay', function (Tournament $tournament) use ($pairingId): void {
            $this->services->tournaments()->desk()->replay($tournament, $this->pages->user(), $pairingId, $this->services->now);
        });
    }

    /**
     * @param array<string, string> $parts
     */
    public function answerDispute(Request $request, array $parts): Response
    {
        $disputeId = PlayPages::idFrom($parts, 'dispute');

        return $this->act($request, $parts, 'disputes', 'tournament.dispute_answered', function (Tournament $tournament) use ($disputeId, $request): void {
            $this->services->tournaments()->desk()->answerDispute(
                $tournament,
                $this->pages->user(),
                $disputeId,
                $request->formValue('answer'),
                $request->formValue('decision', TournamentDesk::DECIDE_DRAW),
                $request->formValue('note'),
                $this->services->now,
            );
        });
    }

    /**
     * @param array<string, string> $parts
     */
    public function pause(Request $request, array $parts): Response
    {
        $isPaused = $request->formValue('paused') === 'yes';

        return $this->act($request, $parts, 'rounds', $isPaused ? 'tournament.paused' : 'tournament.resumed', function (Tournament $tournament) use ($isPaused): void {
            $this->services->tournaments()->desk()->setPaused($tournament, $this->pages->user(), $isPaused, $this->services->now);
        });
    }

    /**
     * @param array<string, string> $parts
     */
    public function cancel(Request $request, array $parts): Response
    {
        return $this->act($request, $parts, 'settings', 'tournament.cancelled', function (Tournament $tournament): void {
            $this->services->tournaments()->tournaments()->cancel($tournament, $this->pages->user(), $this->services->now);
        });
    }

    // --- The League editor (A103) ---

    /**
     * @param array<string, string> $parts
     */
    public function showLeague(Request $request, array $parts): Response
    {
        $tournament = $this->pages->managedTournament($parts);
        if ($tournament === null || $tournament->format !== Tournament::FORMAT_LEAGUE) {
            return $this->pages->notFound($request);
        }

        return $this->pages->render($request, 'tournaments/league', $this->pages->text('league.editor_title'), [
            'tournament' => $tournament,
            'placement' => $this->services->tournaments()->league()->placement($tournament),
            'isEditable' => $tournament->isOpenForRegistration(),
        ]);
    }

    /**
     * Saves a placement. The editor sends one hidden field per division
     * ("division_1" = "12,7,30") and "unplaced"; it asks for JSON, a form
     * without JavaScript gets the page back.
     *
     * @param array<string, string> $parts
     */
    public function saveLeague(Request $request, array $parts): Response
    {
        $tournament = $this->pages->managedTournament($parts);
        if ($tournament === null) {
            return $this->pages->notFound($request);
        }
        $divisions = [];
        for ($division = 1; $division <= $tournament->divisions; $division++) {
            $divisions[] = self::idsFrom($request->formValue('division_' . $division));
        }
        $wantsJson = str_contains($request->header('Accept'), 'application/json');
        try {
            $this->services->tournaments()->league()->save($tournament, $this->pages->user(), $divisions, self::idsFrom($request->formValue('unplaced')), $this->services->now);
        } catch (ActionRefused $refusal) {
            $message = $this->pages->text($refusal->languageKey, $refusal->values);
            if ($wantsJson) {
                return Response::json(['error' => ['code' => $refusal->languageKey, 'message' => $message]], 409);
            }

            return $this->services->pages()->redirectWithMessage($request, '/tournaments/' . $tournament->id . '/league', 'error', $message);
        }
        if ($wantsJson) {
            return Response::json(['saved' => true, 'message' => $this->pages->text('league.saved')]);
        }

        return $this->services->pages()->redirectWithMessage($request, '/tournaments/' . $tournament->id . '/league', 'success', $this->pages->text('league.saved'));
    }

    /**
     * @param array<string, string> $parts
     */
    public function addDivision(Request $request, array $parts): Response
    {
        return $this->actOnLeague($request, $parts, function (Tournament $tournament): void {
            $this->services->tournaments()->league()->addDivision($tournament, $this->pages->user(), $this->services->now);
        });
    }

    /**
     * @param array<string, string> $parts
     */
    public function removeDivision(Request $request, array $parts): Response
    {
        return $this->actOnLeague($request, $parts, function (Tournament $tournament): void {
            $this->services->tournaments()->league()->removeDivision($tournament, $this->pages->user(), $this->services->now);
        });
    }

    /**
     * @param array<string, string> $parts
     */
    public function nextSeason(Request $request, array $parts): Response
    {
        $tournament = $this->pages->managedTournament($parts);
        if ($tournament === null) {
            return $this->pages->notFound($request);
        }
        try {
            $newId = $this->services->tournaments()->league()->createNextSeason($tournament, $this->pages->user(), $this->services->now);
        } catch (ActionRefused $refusal) {
            return $this->services->pages()->redirectWithMessage($request, '/tournaments/' . $tournament->id, 'error', $this->pages->text($refusal->languageKey, $refusal->values));
        }

        return $this->services->pages()->redirectWithMessage($request, '/tournaments/' . $newId . '/league', 'success', $this->pages->text('league.season_created'));
    }

    // --- Helpers ---

    /**
     * Runs one organiser button and goes back to the desk's view.
     *
     * @param array<string, string> $parts
     * @param callable(Tournament): void $action
     */
    private function act(Request $request, array $parts, string $view, string $doneKey, callable $action): Response
    {
        $tournament = $this->pages->managedTournament($parts);
        if ($tournament === null) {
            return $this->pages->notFound($request);
        }

        return $this->pages->run($request, '/tournaments/' . $tournament->id . '/manage?view=' . $view, $doneKey, fn () => $action($tournament));
    }

    /**
     * @param array<string, string> $parts
     * @param callable(Tournament): void $action
     */
    private function actOnLeague(Request $request, array $parts, callable $action): Response
    {
        $tournament = $this->pages->managedTournament($parts);
        if ($tournament === null) {
            return $this->pages->notFound($request);
        }

        return $this->pages->run($request, '/tournaments/' . $tournament->id . '/league', 'league.saved', fn () => $action($tournament));
    }

    /**
     * "12,7,30" -> [12, 7, 30]; anything that is not a positive number is dropped.
     *
     * @return list<int>
     */
    private static function idsFrom(string $text): array
    {
        $ids = [];
        foreach (explode(',', $text) as $part) {
            if (preg_match('/^[1-9][0-9]{0,9}$/', trim($part)) === 1) {
                $ids[] = (int) trim($part);
            }
        }

        return $ids;
    }
}
