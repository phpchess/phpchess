<?php

declare(strict_types=1);

namespace PhpChess\Tests\Tournament;

use PhpChess\Domain\ActionRefused;
use PhpChess\Domain\Identity\User;
use PhpChess\Domain\Play\Game;
use PhpChess\Domain\Play\PlayRefused;
use PhpChess\Domain\Tournament\Pairing;
use PhpChess\Domain\Tournament\TieBreaks;
use PhpChess\Domain\Tournament\Tournament;
use PhpChess\Domain\Tournament\TournamentPlayer;
use PhpChess\Domain\Tournament\TournamentResults;
use PhpChess\Domain\Tournament\TournamentRounds;
use PhpChess\Kernel\PlayParts;
use PhpChess\Kernel\TournamentParts;
use PhpChess\Tests\Support\InstalledSite;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tournaments from start to end on a real database (C4-1: T-01 to T-08,
 * K3, K4, K14, A97 to A109).
 *
 * - One tournament of each format runs to the end with real games; every
 *   board's score comes from its game through Games::end() (K3).
 * - Each forfeit rule at a round deadline, and a board nobody moved on.
 * - The League editor's placement is checked on the server, and the next
 *   season moves players up and down.
 * - Real life: withdrawal, absence, replacement, disputes, corrections.
 * - Closed tournaments can be watched only by their members (A106);
 *   guest logins play only in their tournament (A109).
 */
final class TournamentFlowTest extends TestCase
{
    private const int NOW = 1_800_000_000;
    private const int DAY = 86400;

    private InstalledSite $site;
    private PlayParts $play;
    private TournamentParts $parts;
    private User $organiser;

    protected function setUp(): void
    {
        $this->site = new InstalledSite($this);
        $this->play = new PlayParts($this->site->database, $this->site->settings());
        $this->parts = new TournamentParts($this->site->database, $this->play);
        $organiserId = $this->site->createUser('organiser', User::ROLE_ADMIN);
        $this->organiser = $this->userById($organiserId);
    }

    protected function tearDown(): void
    {
        if (isset($this->site)) {
            $this->site->remove();
        }
    }

    // --- One tournament of each format, to the end ---

    public function testARoundRobinOfFiveRunsToTheEndWithScoresFromTheGames(): void
    {
        $tournament = $this->createWithPlayers(Tournament::FORMAT_ROUND_ROBIN, 5);
        $this->parts->rounds()->start($tournament, $this->organiser, self::NOW);

        // 5 players: 5 rounds, each with two games and one bye worth nothing.
        $rounds = $this->playToTheEnd($tournament->id, Game::RESULT_WHITE_WON);

        $this->assertSame(5, $rounds);
        $finished = $this->tournament($tournament->id);
        $this->assertSame(Tournament::STATUS_FINISHED, $finished->status);
        $pairings = $this->parts->repository()->pairings($tournament->id);
        $this->assertCount(15, $pairings, '10 games and 5 byes');
        $totalHalves = 0;
        foreach ($pairings as $pairing) {
            $totalHalves += $pairing->whiteHalves + $pairing->blackHalves;
            if ($pairing->isBye()) {
                $this->assertSame(0, $pairing->whiteHalves, 'a Round Robin bye is worth nothing (A100)');
            } else {
                $this->assertSame(Pairing::OUTCOME_WHITE_WON, $pairing->outcome, 'the score came from the game (K3)');
            }
        }
        $this->assertSame(20, $totalHalves, '10 decided games, 2 half points each');
    }

    public function testASwissOfSevenRunsItsRoundsWithOneByeEach(): void
    {
        $tournament = $this->createWithPlayers(Tournament::FORMAT_SWISS, 7, ['rounds_planned' => '3']);
        $this->parts->rounds()->start($tournament, $this->organiser, self::NOW);

        $rounds = $this->playToTheEnd($tournament->id, Game::RESULT_DRAW);

        $this->assertSame(3, $rounds);
        $this->assertSame(Tournament::STATUS_FINISHED, $this->tournament($tournament->id)->status);
        $byes = [];
        $meetings = [];
        foreach ($this->parts->repository()->pairings($tournament->id) as $pairing) {
            if ($pairing->isBye()) {
                $byes[] = $pairing->whiteId;
                $this->assertSame(2, $pairing->whiteHalves, 'the Swiss bye is a full point by default');
                continue;
            }
            $key = min($pairing->whiteId, (int) $pairing->blackId) . '-' . max($pairing->whiteId, (int) $pairing->blackId);
            $this->assertArrayNotHasKey($key, $meetings, 'no rematch');
            $meetings[$key] = true;
        }
        $this->assertCount(3, $byes);
        $this->assertSame($byes, array_values(array_unique($byes)), 'nobody gets the bye twice');
    }

    public function testAKnockoutGoesToArmageddonOnADrawAndBlackWinsItsDraw(): void
    {
        $tournament = $this->createWithPlayers(Tournament::FORMAT_KNOCKOUT, 4, ['third_place' => 'yes']);
        $this->parts->rounds()->start($tournament, $this->organiser, self::NOW);

        // Semi-finals end in draws: each match gets an armageddon game.
        $this->endRunningGames($tournament->id, Game::RESULT_DRAW);
        $this->parts->rounds()->tick($tournament->id, self::NOW);
        $armageddons = array_values(array_filter($this->parts->repository()->pairings($tournament->id), fn (Pairing $pairing): bool => $pairing->isArmageddon));
        $this->assertCount(2, $armageddons);
        foreach ($armageddons as $armageddon) {
            $this->assertSame(Pairing::STATE_PLAYING, $armageddon->state);
        }

        // The armageddon games are drawn too: Black goes through (draw odds, A100).
        $this->endRunningGames($tournament->id, Game::RESULT_DRAW);
        $this->parts->rounds()->tick($tournament->id, self::NOW);
        $finalBoards = array_values(array_filter($this->parts->repository()->pairings($tournament->id), fn (Pairing $pairing): bool => $pairing->round === 2));
        $this->assertCount(2, $finalBoards, 'the final and the match for third place');
        $blackWinners = array_map(fn (Pairing $pairing): int => (int) $pairing->blackId, $armageddons);
        $this->assertEqualsCanonicalizing($blackWinners, [$finalBoards[0]->whiteId, (int) $finalBoards[0]->blackId]);

        $this->endRunningGames($tournament->id, Game::RESULT_WHITE_WON);
        $this->parts->rounds()->tick($tournament->id, self::NOW);
        $this->assertSame(Tournament::STATUS_FINISHED, $this->tournament($tournament->id)->status);
    }

    public function testALeagueRunsInTwoDivisionsAndTheNextSeasonMovesPlayers(): void
    {
        $tournament = $this->createWithPlayers(Tournament::FORMAT_LEAGUE, 6, ['divisions' => '2', 'promote_count' => '1', 'relegate_count' => '1']);
        $ids = $this->playerIds($tournament->id);
        $this->parts->league()->save($tournament, $this->organiser, [[$ids[0], $ids[1], $ids[2]], [$ids[3], $ids[4], $ids[5]]], [], self::NOW);
        $this->parts->rounds()->start($this->tournament($tournament->id), $this->organiser, self::NOW);

        $this->playToTheEnd($tournament->id, Game::RESULT_WHITE_WON);
        $season = $this->tournament($tournament->id);
        $this->assertSame(Tournament::STATUS_FINISHED, $season->status);

        $order = $this->parts->league()->standingsByDivision($season);
        $nextId = $this->parts->league()->createNextSeason($season, $this->organiser, self::NOW);
        $next = $this->parts->league()->placement($this->tournament($nextId));
        $divisionOne = array_map(fn (TournamentPlayer $player): int => $player->userId, $next['divisions'][1]);
        $divisionTwo = array_map(fn (TournamentPlayer $player): int => $player->userId, $next['divisions'][2]);

        $this->assertContains($order[2][0], $divisionOne, 'the winner of division 2 goes up');
        $this->assertContains($order[1][2], $divisionTwo, 'the last of division 1 goes down');
        $this->assertSame(2, $this->tournament($nextId)->season);
        $this->assertSame($nextId, $this->parts->league()->createNextSeason($season, $this->organiser, self::NOW), 'made only once');
    }

    // --- The forfeit rules at the deadline (A102) ---

    /**
     * @return array<string, array{string, string, int, int}>
     */
    public static function forfeitRules(): array
    {
        return [
            'player to move loses' => [TournamentRounds::FORFEIT_LOSS_ON_TIME, Pairing::OUTCOME_WHITE_FORFEIT_WIN, 2, 0],
            'counts as a draw' => [TournamentRounds::FORFEIT_DRAW, Pairing::OUTCOME_ADJUDICATED_DRAW, 1, 1],
        ];
    }

    #[DataProvider('forfeitRules')]
    public function testARunningGameAtTheDeadlineIsDecidedByTheRuleAndNotRated(string $rule, string $outcome, int $whiteHalves, int $blackHalves): void
    {
        $tournament = $this->createWithPlayers(Tournament::FORMAT_ROUND_ROBIN, 2, ['forfeit_rule' => $rule, 'round_days' => '2']);
        $this->parts->rounds()->start($tournament, $this->organiser, self::NOW);
        $board = $this->onlyBoard($tournament->id);
        $this->play->games()->move((int) $board->gameId, $board->whiteId, 0, 'e2e4', self::NOW);

        $this->parts->rounds()->tick($tournament->id, self::NOW + self::DAY);
        $this->assertSame(Pairing::STATE_PLAYING, $this->onlyBoard($tournament->id)->state, 'not before the deadline');

        $this->parts->rounds()->tick($tournament->id, self::NOW + 3 * self::DAY);
        $decided = $this->onlyBoard($tournament->id);
        $this->assertSame($outcome, $decided->outcome);
        $this->assertSame([$whiteHalves, $blackHalves], [$decided->whiteHalves, $decided->blackHalves]);
        $game = $this->play->gameRepository()->find((int) $board->gameId);
        $this->assertNotNull($game);
        $this->assertSame(TournamentResults::REASON_ROUND_DEADLINE, $game->endReason);
        $this->assertSame(0, $this->ratingRowsOf((int) $board->gameId), 'a forfeit is never rated');
        $this->assertSame(Tournament::STATUS_FINISHED, $this->tournament($tournament->id)->status);
    }

    public function testNobodyMovedIsADoubleForfeit(): void
    {
        $tournament = $this->createWithPlayers(Tournament::FORMAT_ROUND_ROBIN, 2, ['round_days' => '1']);
        $this->parts->rounds()->start($tournament, $this->organiser, self::NOW);

        $this->parts->rounds()->tick($tournament->id, self::NOW + 2 * self::DAY);

        $board = $this->onlyBoard($tournament->id);
        $this->assertSame(Pairing::OUTCOME_DOUBLE_FORFEIT, $board->outcome);
        $this->assertSame([0, 0], [$board->whiteHalves, $board->blackHalves]);
    }

    public function testWithTheOrganiserRuleTheRoundWaitsForTheDecision(): void
    {
        $tournament = $this->createWithPlayers(Tournament::FORMAT_ROUND_ROBIN, 2, ['forfeit_rule' => TournamentRounds::FORFEIT_ORGANISER, 'round_days' => '1']);
        $this->parts->rounds()->start($tournament, $this->organiser, self::NOW);
        $board = $this->onlyBoard($tournament->id);
        $this->play->games()->move((int) $board->gameId, $board->whiteId, 0, 'e2e4', self::NOW);

        $this->parts->rounds()->tick($tournament->id, self::NOW + 5 * self::DAY);
        $this->assertSame(Pairing::STATE_PLAYING, $this->onlyBoard($tournament->id)->state, 'the round stays open');

        $this->parts->desk()->decide($this->tournament($tournament->id), $this->organiser, $board->id, 'black_won', self::NOW + 5 * self::DAY);
        $decided = $this->onlyBoard($tournament->id);
        $this->assertSame(Pairing::OUTCOME_BLACK_WON, $decided->outcome);
        $this->assertSame(Tournament::STATUS_FINISHED, $this->tournament($tournament->id)->status);
    }

    // --- The League editor is checked on the server (A103) ---

    public function testTheLeaguePlacementIsCheckedOnTheServer(): void
    {
        $tournament = $this->createWithPlayers(Tournament::FORMAT_LEAGUE, 4, ['divisions' => '2']);
        $ids = $this->playerIds($tournament->id);
        $outsiderId = $this->site->createUser('outsider');

        $refused = [
            'a player twice' => [[[$ids[0], $ids[1]], [$ids[1], $ids[2]]], [$ids[3]]],
            'a stranger' => [[[$ids[0], $ids[1]], [$ids[2], $outsiderId]], [$ids[3]]],
            'a player missing' => [[[$ids[0], $ids[1]], [$ids[2]]], []],
            'too many divisions' => [[[$ids[0]], [$ids[1]], [$ids[2], $ids[3]]], []],
        ];
        foreach ($refused as $case => [$divisions, $unplaced]) {
            try {
                $this->parts->league()->save($tournament, $this->organiser, $divisions, $unplaced, self::NOW);
                $this->fail("{$case} was accepted");
            } catch (ActionRefused) {
                $this->addToAssertionCount(1);
            }
        }

        $this->parts->league()->save($tournament, $this->organiser, [[$ids[2], $ids[0]], [$ids[1]]], [$ids[3]], self::NOW);
        $placement = $this->parts->league()->placement($tournament);
        $this->assertSame([$ids[2], $ids[0]], array_map(fn (TournamentPlayer $player): int => $player->userId, $placement['divisions'][1]));
        $this->assertSame([$ids[3]], array_map(fn (TournamentPlayer $player): int => $player->userId, $placement['unplaced']));

        // Someone who is not an organiser may not change it (K14).
        $player = $this->userById($ids[0]);
        $this->expectException(ActionRefused::class);
        $this->parts->league()->save($tournament, $player, [[$ids[0], $ids[1]], [$ids[2], $ids[3]]], [], self::NOW);
    }

    // --- Real life (A108) ---

    public function testAnEarlyWithdrawalInARoundRobinRemovesAllResults(): void
    {
        $tournament = $this->createWithPlayers(Tournament::FORMAT_ROUND_ROBIN, 4);
        $this->parts->rounds()->start($tournament, $this->organiser, self::NOW);
        $leaver = $this->playerIds($tournament->id)[0];

        $this->parts->desk()->withdraw($this->tournament($tournament->id), $this->userById($leaver), $leaver, self::NOW);

        foreach ($this->parts->repository()->pairings($tournament->id) as $pairing) {
            if ($pairing->involves($leaver)) {
                $this->assertSame(Pairing::STATE_VOID, $pairing->state);
            }
        }
        $states = array_map(fn (TournamentPlayer $player): string => $player->state, array_filter($this->parts->repository()->players($tournament->id), fn (TournamentPlayer $player): bool => $player->userId === $leaver));
        $this->assertSame([TournamentPlayer::STATE_REMOVED], array_values($states));
        // The tournament goes on without them.
        $this->playToTheEnd($tournament->id, Game::RESULT_BLACK_WON);
        $this->assertSame(Tournament::STATUS_FINISHED, $this->tournament($tournament->id)->status);
    }

    public function testAnAbsentPlayersBoardIsPostponedAndStartsWhenTheyAreBack(): void
    {
        $tournament = $this->createWithPlayers(Tournament::FORMAT_ROUND_ROBIN, 4, ['confirm_pairings' => 'yes']);
        $this->parts->rounds()->start($tournament, $this->organiser, self::NOW);
        $away = $this->playerIds($tournament->id)[0];
        $this->parts->desk()->setAbsent($this->tournament($tournament->id), $this->userById($away), $away, 1, true, self::NOW);
        $this->parts->rounds()->startPairedRound($this->tournament($tournament->id), $this->organiser, self::NOW);

        $boards = array_values(array_filter($this->parts->repository()->pairings($tournament->id), fn (Pairing $pairing): bool => $pairing->involves($away)));
        $this->assertSame(Pairing::STATE_POSTPONED, $boards[0]->state);

        // Round 1 closes without that board; round 2 starts the postponed board too.
        $this->endRunningGames($tournament->id, Game::RESULT_DRAW);
        $this->parts->rounds()->tick($tournament->id, self::NOW);
        $this->parts->rounds()->startPairedRound($this->tournament($tournament->id), $this->organiser, self::NOW);
        $this->parts->rounds()->tick($tournament->id, self::NOW);
        $this->assertSame(Pairing::STATE_PLAYING, $this->parts->repository()->findPairing($boards[0]->id)?->state);
    }

    public function testADisputeCanCorrectAFinishedResultThroughTheGame(): void
    {
        $tournament = $this->createWithPlayers(Tournament::FORMAT_ROUND_ROBIN, 2);
        $this->parts->rounds()->start($tournament, $this->organiser, self::NOW);
        $board = $this->onlyBoard($tournament->id);
        $this->play->games()->end((int) $board->gameId, Game::RESULT_WHITE_WON, 'resignation', self::NOW);
        $loser = $this->userById((int) $board->blackId);

        $disputeId = $this->parts->desk()->raiseDispute($this->tournament($tournament->id), $loser, $board->id, 'I never resigned.', self::NOW);
        $this->parts->desk()->answerDispute($this->tournament($tournament->id), $this->organiser, $disputeId, 'correct', 'draw', 'Agreed draw.', self::NOW);

        $corrected = $this->onlyBoard($tournament->id);
        $this->assertSame([1, 1], [$corrected->whiteHalves, $corrected->blackHalves]);
        $game = $this->play->gameRepository()->find((int) $board->gameId);
        $this->assertSame(Game::RESULT_DRAW, $game?->result, 'the game itself carries the corrected result (K3)');
        $this->assertSame(1, $this->ratingRowsOf((int) $board->gameId) / 2, 'rated once, not again (K4)');
    }

    public function testOnlyThePlayersOfABoardMayRaiseADispute(): void
    {
        $tournament = $this->createWithPlayers(Tournament::FORMAT_ROUND_ROBIN, 4);
        $this->parts->rounds()->start($tournament, $this->organiser, self::NOW);
        $boards = $this->parts->repository()->pairings($tournament->id);
        $stranger = null;
        foreach ($this->playerIds($tournament->id) as $userId) {
            if (!$boards[0]->involves($userId)) {
                $stranger = $userId;
            }
        }

        $this->expectException(ActionRefused::class);
        $this->parts->desk()->raiseDispute($this->tournament($tournament->id), $this->userById((int) $stranger), $boards[0]->id, 'Not my game.', self::NOW);
    }

    // --- Watching and guests (A106, A109) ---

    public function testOnlyMembersWatchAClosedTournament(): void
    {
        $closed = $this->createWithPlayers(Tournament::FORMAT_ROUND_ROBIN, 2, ['registration' => Tournament::REGISTRATION_ORGANISER]);
        $open = $this->createWithPlayers(Tournament::FORMAT_ROUND_ROBIN, 2, ['registration' => Tournament::REGISTRATION_OPEN], 'open');
        $this->parts->rounds()->start($closed, $this->organiser, self::NOW);
        $this->parts->rounds()->start($open, $this->organiser, self::NOW);
        $outsider = $this->userById($this->site->createUser('watcher'));
        $closedBoard = $this->onlyBoard($closed->id);
        $openBoard = $this->onlyBoard($open->id);

        $access = $this->parts->access();
        $this->assertFalse($access->mayWatchGame((int) $closedBoard->gameId, $outsider));
        $this->assertTrue($access->mayWatchGame((int) $closedBoard->gameId, $this->userById($closedBoard->whiteId)));
        $this->assertTrue($access->mayWatchGame((int) $openBoard->gameId, $outsider));
        $this->assertFalse($access->maySee($this->tournament($closed->id), $outsider));
    }

    public function testGuestLoginsPlayOnlyInTheirTournament(): void
    {
        $tournament = $this->createWithPlayers(Tournament::FORMAT_ROUND_ROBIN, 1);
        $logins = $this->parts->tournaments()->createGuests($tournament, $this->organiser, 'visitors', 2, self::NOW);
        $this->assertCount(2, $logins);
        $guest = $this->site->users()->findByUsername((string) array_key_first($logins));
        $this->assertNotNull($guest);
        $this->assertTrue(password_verify((string) reset($logins), $this->site->users()->passwordHashFor($guest->id)));

        $this->parts->rounds()->start($this->tournament($tournament->id), $this->organiser, self::NOW);
        $this->assertNotNull($this->parts->repository()->pairings($tournament->id)[0]->gameId, 'tournament games start for guests');

        $this->expectException(PlayRefused::class);
        $this->play->games()->start($guest->id, $this->organiser->id, 3, false, '', self::NOW);
    }

    public function testOutsidersCannotManage(): void
    {
        $tournament = $this->createWithPlayers(Tournament::FORMAT_SWISS, 2);
        $outsider = $this->userById($this->site->createUser('meddler'));

        $this->expectException(ActionRefused::class);
        $this->parts->rounds()->start($tournament, $outsider, self::NOW);
    }

    public function testTheDefaultTieBreaksFollowTheFormat(): void
    {
        $swiss = $this->createWithPlayers(Tournament::FORMAT_SWISS, 0);

        $this->assertSame(TieBreaks::defaultsFor(Tournament::FORMAT_SWISS), $this->tournament($swiss->id)->tieBreaks);
    }

    // --- Helpers ---

    /**
     * Creates a tournament (registration by the organiser unless set) with
     * $count new players.
     *
     * @param array<string, string> $settings
     */
    private function createWithPlayers(string $format, int $count, array $settings = [], string $namePrefix = 'p'): Tournament
    {
        $id = $this->parts->tournaments()->create($this->organiser, $settings + [
            'name' => 'Test ' . $format,
            'format' => $format,
            'registration' => Tournament::REGISTRATION_ORGANISER,
            'is_rated' => 'yes',
        ], self::NOW);
        $tournament = $this->tournament($id);
        for ($i = 1; $i <= $count; $i++) {
            $username = $namePrefix . $id . '_' . $i;
            $this->site->createUser($username);
            $this->parts->tournaments()->addPlayerByName($tournament, $this->organiser, $username, self::NOW);
        }

        return $this->tournament($id);
    }

    /**
     * Ends every running game with the result, round after round, until the
     * tournament is finished. Returns the number of rounds played.
     */
    private function playToTheEnd(int $tournamentId, string $result): int
    {
        for ($guard = 0; $guard < 40; $guard++) {
            $tournament = $this->tournament($tournamentId);
            if ($tournament->status !== Tournament::STATUS_RUNNING) {
                return $tournament->currentRound;
            }
            $this->endRunningGames($tournamentId, $result);
            $this->parts->rounds()->tick($tournamentId, self::NOW);
        }
        $this->fail('the tournament did not finish');
    }

    private function endRunningGames(int $tournamentId, string $result): void
    {
        foreach ($this->parts->repository()->pairings($tournamentId) as $pairing) {
            if ($pairing->state === Pairing::STATE_PLAYING && $pairing->gameId !== null) {
                $this->play->games()->end($pairing->gameId, $result, $result === Game::RESULT_DRAW ? 'draw_agreed' : 'resignation', self::NOW);
            }
        }
    }

    private function onlyBoard(int $tournamentId): Pairing
    {
        $boards = array_values(array_filter($this->parts->repository()->pairings($tournamentId), fn (Pairing $pairing): bool => !$pairing->isBye()));
        $this->assertCount(1, $boards);

        return $boards[0];
    }

    /** @return list<int> */
    private function playerIds(int $tournamentId): array
    {
        return array_map(fn (TournamentPlayer $player): int => $player->userId, $this->parts->repository()->players($tournamentId));
    }

    private function tournament(int $id): Tournament
    {
        $tournament = $this->parts->repository()->find($id);
        $this->assertNotNull($tournament);

        return $tournament;
    }

    private function userById(int $id): User
    {
        $user = $this->site->users()->findById($id);
        $this->assertNotNull($user);

        return $user;
    }

    private function ratingRowsOf(int $gameId): int
    {
        $count = $this->site->database->fetchValue('SELECT COUNT(*) FROM ' . $this->site->database->table('rating_history') . ' WHERE game_id = ?', [$gameId]);

        return is_numeric($count) ? (int) $count : 0;
    }
}
