<?php

declare(strict_types=1);

namespace PhpChess\Domain\Tournament;

use PhpChess\Domain\ActionRefused;
use PhpChess\Domain\Identity\User;
use PhpChess\Infra\Tournament\TournamentRepository;

/**
 * The League's divisions (T-05, A15, A103): placing players with the
 * drag-and-drop editor, and the next season with promotion and relegation.
 *
 * A League season is one tournament of the format "league". Division 1 is
 * the top. Each division plays a Round Robin (TournamentRounds). When a
 * season is finished, the organiser makes the next one: the top players of
 * each division move up, the bottom ones move down (promote_count and
 * relegate_count), and the new season opens for changes in the editor
 * before it starts.
 *
 * The editor (templates/tournaments/league.php, league-editor.js) sends the
 * whole placement after every change; save() checks it on the server: every
 * player exactly once, only players of this tournament, no more divisions
 * than set, and only before the season starts.
 */
final class LeagueDivisions
{
    public const int MOST_DIVISIONS = 20;

    public function __construct(
        private readonly TournamentRepository $tournaments,
        private readonly TournamentAccess $access,
        private readonly TournamentRounds $rounds,
    ) {
    }

    /**
     * The placement for the editor: each division's players in seed order,
     * and the players not placed yet.
     *
     * @return array{divisions: array<int, list<TournamentPlayer>>, unplaced: list<TournamentPlayer>}
     */
    public function placement(Tournament $tournament): array
    {
        $grouped = $this->rounds->divisionsOf($this->tournaments->players($tournament->id));
        $divisions = [];
        for ($division = 1; $division <= $tournament->divisions; $division++) {
            $divisions[$division] = $grouped[$division] ?? [];
        }
        // Players in a division beyond the set number (it was made smaller) count as unplaced.
        $unplaced = $grouped[0] ?? [];
        foreach ($grouped as $division => $players) {
            if ($division > $tournament->divisions) {
                $unplaced = [...$unplaced, ...$players];
            }
        }

        return ['divisions' => $divisions, 'unplaced' => $unplaced];
    }

    /**
     * Saves a placement from the editor, checked on the server.
     *
     * @param list<list<int>> $divisions  division 1 first; each a list of user ids, top seed first
     * @param list<int> $unplaced
     */
    public function save(Tournament $tournament, User $manager, array $divisions, array $unplaced, int $now): void
    {
        $this->checkEditable($tournament, $manager);
        if (count($divisions) > $tournament->divisions) {
            throw new ActionRefused('league.too_many_divisions');
        }
        $playerIds = array_map(fn (TournamentPlayer $player): int => $player->userId, $this->tournaments->players($tournament->id));
        $seen = [];
        foreach ([...$divisions, $unplaced] as $list) {
            foreach ($list as $userId) {
                if (!in_array($userId, $playerIds, true) || isset($seen[$userId])) {
                    throw new ActionRefused('league.placement_invalid');
                }
                $seen[$userId] = true;
            }
        }
        if (count($seen) !== count($playerIds)) {
            throw new ActionRefused('league.placement_invalid');
        }

        foreach ($divisions as $index => $list) {
            foreach ($list as $position => $userId) {
                $this->tournaments->setSeedAndDivision($tournament->id, $userId, $position + 1, $index + 1);
            }
        }
        foreach ($unplaced as $position => $userId) {
            $this->tournaments->setSeedAndDivision($tournament->id, $userId, $position + 1, 0);
        }
        $this->tournaments->log($tournament->id, $manager->id, 'league_placement', count($seen) . ' players placed', $now);
    }

    /** One more division at the bottom. */
    public function addDivision(Tournament $tournament, User $manager, int $now): void
    {
        $this->checkEditable($tournament, $manager);
        if ($tournament->divisions >= self::MOST_DIVISIONS) {
            throw new ActionRefused('league.too_many_divisions');
        }
        $this->tournaments->updateSettings($tournament->id, ['divisions' => $tournament->divisions + 1]);
        $this->tournaments->log($tournament->id, $manager->id, 'league_division_added', (string) ($tournament->divisions + 1), $now);
    }

    /** Removes the bottom division; its players become unplaced. */
    public function removeDivision(Tournament $tournament, User $manager, int $now): void
    {
        $this->checkEditable($tournament, $manager);
        if ($tournament->divisions <= 1) {
            throw new ActionRefused('league.one_division_needed');
        }
        foreach ($this->tournaments->players($tournament->id) as $player) {
            if ($player->division >= $tournament->divisions) {
                $this->tournaments->setSeedAndDivision($tournament->id, $player->userId, $player->seed, 0);
            }
        }
        $this->tournaments->updateSettings($tournament->id, ['divisions' => $tournament->divisions - 1]);
        $this->tournaments->log($tournament->id, $manager->id, 'league_division_removed', (string) $tournament->divisions, $now);
    }

    // --- The next season (A103) ---

    /**
     * Makes the next season of a finished League: same settings, the
     * players placed by promotion and relegation. Returns its id.
     */
    public function createNextSeason(Tournament $season, User $manager, int $now): int
    {
        if (!$this->access->mayManage($season, $manager)) {
            throw new ActionRefused('tournament.not_allowed');
        }
        if ($season->format !== Tournament::FORMAT_LEAGUE || $season->status !== Tournament::STATUS_FINISHED) {
            throw new ActionRefused('league.season_not_finished');
        }
        $existing = $this->tournaments->nextSeasonId($season->id);
        if ($existing !== null) {
            return $existing;
        }

        $newDivisions = self::nextPlacement(
            $this->standingsByDivision($season),
            $season->divisions,
            $season->promoteCount,
            $season->relegateCount,
        );

        $settings = [
            'name' => $season->name, 'description' => $season->description, 'format' => $season->format,
            'registration' => $season->registration, 'max_players' => $season->maxPlayers, 'pace' => $season->pace,
            'days_per_move' => $season->daysPerMove, 'time_control' => $season->timeControl, 'round_days' => $season->roundDays,
            'double_round' => $season->isDoubleRound, 'is_rated' => $season->isRated, 'confirm_pairings' => $season->confirmsPairings,
            'forfeit_rule' => $season->forfeitRule, 'tie_breaks' => implode(',', $season->tieBreaks), 'divisions' => $season->divisions,
            'promote_count' => $season->promoteCount, 'relegate_count' => $season->relegateCount,
        ];
        $newId = $this->tournaments->create($settings, $manager->id, $season->season + 1, $season->id, $now);
        foreach ($this->tournaments->organiserIds($season->id) as $organiserId) {
            $this->tournaments->addOrganiser($newId, $organiserId);
        }
        foreach ($newDivisions as $division => $userIds) {
            foreach ($userIds as $position => $userId) {
                $this->tournaments->addPlayer($newId, $userId, $position + 1, $division, 0, 1, $now);
            }
        }
        $this->tournaments->log($newId, $manager->id, 'season_created', 'Season ' . ($season->season + 1), $now);
        $this->tournaments->log($season->id, $manager->id, 'next_season', 'Season ' . ($season->season + 1), $now);

        return $newId;
    }

    /**
     * Promotion and relegation (A103), pure: from each division's final
     * order, the top $promote of division d (d > 1) go up to d - 1 and the
     * bottom $relegate of division d (d < last) go down to d + 1.
     *
     * In the new division, those coming down from above are seeded first,
     * then those who stayed, in their order, then those coming up from below.
     *
     * @param array<int, list<int>> $finalOrder  division => user ids, winner first (players who left are not in it)
     * @return array<int, list<int>>
     */
    public static function nextPlacement(array $finalOrder, int $divisionCount, int $promote, int $relegate): array
    {
        $up = [];
        $down = [];
        $stay = [];
        for ($division = 1; $division <= $divisionCount; $division++) {
            $order = $finalOrder[$division] ?? [];
            $size = count($order);
            $goingUp = $division > 1 ? min($promote, $size) : 0;
            $goingDown = $division < $divisionCount ? min($relegate, max(0, $size - $goingUp)) : 0;
            $up[$division] = array_slice($order, 0, $goingUp);
            $down[$division] = $goingDown > 0 ? array_slice($order, $size - $goingDown) : [];
            $stay[$division] = array_slice($order, $goingUp, $size - $goingUp - $goingDown);
        }
        $placement = [];
        for ($division = 1; $division <= $divisionCount; $division++) {
            $placement[$division] = [
                ...($down[$division - 1] ?? []),
                ...$stay[$division],
                ...($up[$division + 1] ?? []),
            ];
        }

        return $placement;
    }

    /**
     * Each division's final order (user ids, winner first), without players who left.
     *
     * @return array<int, list<int>>
     */
    public function standingsByDivision(Tournament $season): array
    {
        $pairings = $this->tournaments->pairings($season->id);
        $order = [];
        foreach ($this->rounds->divisionsOf($this->tournaments->players($season->id)) as $division => $players) {
            if ($division === 0) {
                continue;
            }
            $divisionPairings = array_values(array_filter($pairings, fn (Pairing $pairing): bool => $pairing->division === $division));
            foreach (TieBreaks::standings($players, $divisionPairings, $season->tieBreaks) as $row) {
                if ($row->player->isActive()) {
                    $order[$division][] = $row->player->userId;
                }
            }
        }

        return $order;
    }

    private function checkEditable(Tournament $tournament, User $manager): void
    {
        if (!$this->access->mayManage($tournament, $manager)) {
            throw new ActionRefused('tournament.not_allowed');
        }
        if ($tournament->format !== Tournament::FORMAT_LEAGUE) {
            throw new ActionRefused('league.not_a_league');
        }
        if (!$tournament->isOpenForRegistration()) {
            throw new ActionRefused('tournament.already_started');
        }
    }
}
