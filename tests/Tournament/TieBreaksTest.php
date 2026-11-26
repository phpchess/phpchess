<?php

declare(strict_types=1);

namespace PhpChess\Tests\Tournament;

use PhpChess\Domain\Tournament\LeagueDivisions;
use PhpChess\Domain\Tournament\Pairing;
use PhpChess\Domain\Tournament\TieBreaks;
use PhpChess\Domain\Tournament\TournamentPlayer;
use PHPUnit\Framework\TestCase;

/**
 * Tie-break values and standings (T-06, A101), and promotion and
 * relegation in the League (T-05, A103). Pure code: no database.
 *
 * The example: a Round Robin of four, A, B, C, D (ids 1 to 4).
 *   A beat B, A drew C, A beat D       -> 2½
 *   B beat C, B beat D                 -> 2
 *   C beat D, C drew A                 -> 1½
 *   D                                  -> 0
 * Worked by hand:
 *   Buchholz A = B 2 + C 1½ + D 0 = 3½;   cut-1 drops D's 0 = 3½
 *   Buchholz B = A 2½ + C 1½ + D 0 = 4;   cut-1 = 4
 *   Sonneborn-Berger A = B 2 + ½·C 1½ + D 0 = 2.75
 *   Sonneborn-Berger B = C 1½ + D 0 = 1½
 */
final class TieBreaksTest extends TestCase
{
    /** Every test board gets its own id (and game id). */
    private static int $nextBoardId = 100;

    public function testTheFourPlayerExampleWorkedByHand(): void
    {
        [$players, $pairings] = self::roundRobinOfFour();
        $rows = TieBreaks::standings($players, $pairings, [TieBreaks::BUCHHOLZ, TieBreaks::BUCHHOLZ_CUT1, TieBreaks::SONNEBORN_BERGER, TieBreaks::WINS]);

        $this->assertSame([1, 2, 3, 4], array_map(fn ($row): int => $row->player->userId, $rows));
        $this->assertSame([5, 4, 3, 0], array_map(fn ($row): int => $row->halves, $rows));
        $this->assertSame(3.5, $rows[0]->tieBreaks[TieBreaks::BUCHHOLZ]);
        $this->assertSame(3.5, $rows[0]->tieBreaks[TieBreaks::BUCHHOLZ_CUT1]);
        $this->assertSame(2.75, $rows[0]->tieBreaks[TieBreaks::SONNEBORN_BERGER]);
        $this->assertSame(4.0, $rows[1]->tieBreaks[TieBreaks::BUCHHOLZ]);
        $this->assertSame(1.5, $rows[1]->tieBreaks[TieBreaks::SONNEBORN_BERGER]);
        $this->assertSame(2.0, $rows[0]->tieBreaks[TieBreaks::WINS]);
    }

    public function testDirectEncounterDecidesBetweenTwoLevelPlayersWhoMet(): void
    {
        $players = [self::player(1, 1), self::player(2, 2), self::player(3, 3)];
        $pairings = [
            self::board(1, 1, 2, 2, 0),   // 1 beat 2
            self::board(2, 2, 3, 2, 0),   // 2 beat 3
            self::board(3, 3, 1, 2, 0),   // 3 beat 1
        ];
        // Everyone has 1 point and all met: direct encounter gives everyone 1, still level.
        $rows = TieBreaks::standings($players, $pairings, [TieBreaks::DIRECT_ENCOUNTER]);
        $this->assertSame([1, 1, 1], array_map(fn ($row): int => $row->rank, $rows));

        // Two level players who met: the winner of their game is ahead, whatever the seeds say.
        $players = [self::player(1, 2), self::player(2, 1), self::player(3, 3)];
        $pairings = [
            self::board(1, 1, 2, 2, 0),   // 1 beat 2
            self::board(2, 2, 3, 2, 0),   // 2 beat 3: 1 and 2 both have 1 point
        ];
        $rows = TieBreaks::standings($players, $pairings, [TieBreaks::DIRECT_ENCOUNTER]);
        $this->assertSame([1, 2, 3], array_map(fn ($row): int => $row->player->userId, $rows));
        $this->assertSame([1, 2, 3], array_map(fn ($row): int => $row->rank, $rows));
    }

    public function testAByeCountsAsAVirtualOpponentWithTheOwnScore(): void
    {
        $players = [self::player(1, 1), self::player(2, 2), self::player(3, 3)];
        $pairings = [
            self::board(1, 1, 2, 2, 0),
            new Pairing(9, 1, 1, 0, 2, 0, 1, false, 3, null, null, Pairing::STATE_DONE, Pairing::OUTCOME_BYE, 2, 0, null),
        ];
        $rows = TieBreaks::standings($players, $pairings, [TieBreaks::BUCHHOLZ]);
        $byId = [];
        foreach ($rows as $row) {
            $byId[$row->player->userId] = $row;
        }

        // Player 3's only "opponent" is the bye: their own final score, 1.
        $this->assertSame(1.0, $byId[3]->tieBreaks[TieBreaks::BUCHHOLZ]);
        $this->assertSame(0, $byId[3]->gamesPlayed);
    }

    public function testVoidAndAbortedBoardsDoNotCount(): void
    {
        $players = [self::player(1, 1), self::player(2, 2)];
        $pairings = [
            new Pairing(1, 1, 1, 0, 1, 0, 1, false, 1, 2, 5, Pairing::STATE_VOID, Pairing::OUTCOME_WHITE_WON, 2, 0, null),
            new Pairing(2, 1, 2, 0, 1, 0, 1, false, 2, 1, 6, Pairing::STATE_DONE, Pairing::OUTCOME_ABORTED, 0, 0, null),
        ];

        $rows = TieBreaks::standings($players, $pairings, []);
        $this->assertSame([0, 0], array_map(fn ($row): int => $row->halves, $rows));
    }

    // --- Promotion and relegation (A103) ---

    public function testTheTopGoUpAndTheBottomGoDown(): void
    {
        $finalOrder = [
            1 => [11, 12, 13, 14],
            2 => [21, 22, 23, 24],
            3 => [31, 32, 33, 34],
        ];

        $next = LeagueDivisions::nextPlacement($finalOrder, 3, 1, 1);

        $this->assertSame([11, 12, 13, 21], $next[1]);
        $this->assertSame([14, 22, 23, 31], $next[2]);
        $this->assertSame([24, 32, 33, 34], $next[3]);
    }

    public function testSmallDivisionsSwapWhenTheCountsSaySo(): void
    {
        // Division 1 has two players and two go down; division 2's only player goes up.
        $next = LeagueDivisions::nextPlacement([1 => [1, 2], 2 => [3]], 2, 2, 2);

        $this->assertSame([3], $next[1]);
        $this->assertSame([1, 2], $next[2]);
    }

    // --- Helpers ---

    /**
     * @return array{0: list<TournamentPlayer>, 1: list<Pairing>}
     */
    private static function roundRobinOfFour(): array
    {
        $players = [self::player(1, 1), self::player(2, 2), self::player(3, 3), self::player(4, 4)];
        $pairings = [
            self::board(1, 1, 2, 2, 0),   // A-B 1-0
            self::board(1, 3, 4, 2, 0),   // C-D 1-0
            self::board(2, 1, 3, 1, 1),   // A-C ½-½
            self::board(2, 2, 4, 2, 0),   // B-D 1-0
            self::board(3, 4, 1, 0, 2),   // D-A 0-1
            self::board(3, 2, 3, 2, 0),   // B-C 1-0
        ];

        return [$players, $pairings];
    }

    private static function player(int $userId, int $seed): TournamentPlayer
    {
        return new TournamentPlayer(1, $userId, 'player' . $userId, TournamentPlayer::STATE_ACTIVE, $seed, 0, 1500, 1, null, false, false);
    }

    private static function board(int $round, int $whiteId, int $blackId, int $whiteHalves, int $blackHalves): Pairing
    {
        $outcome = match (true) {
            $whiteHalves > $blackHalves => Pairing::OUTCOME_WHITE_WON,
            $whiteHalves < $blackHalves => Pairing::OUTCOME_BLACK_WON,
            default => Pairing::OUTCOME_DRAW,
        };
        self::$nextBoardId++;
        $id = self::$nextBoardId;

        return new Pairing($id, 1, $round, 0, 1, 0, 1, false, $whiteId, $blackId, $id, Pairing::STATE_DONE, $outcome, $whiteHalves, $blackHalves, null);
    }
}
