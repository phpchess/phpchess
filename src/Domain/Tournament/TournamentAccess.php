<?php

declare(strict_types=1);

namespace PhpChess\Domain\Tournament;

use PhpChess\Domain\Identity\User;
use PhpChess\Infra\Tournament\TournamentRepository;

/**
 * Who may do what with a tournament (K14, A98, A106).
 *
 * - **Create**: admins, and members an admin allowed to organise (A98).
 * - **Manage**: admins, the creator and the co-organisers.
 * - **See and watch**: everyone (members) for an open tournament; only its
 *   players and organisers (and admins) for a closed one (A106).
 *
 * Asked by the tournament pages before a service runs, and by
 * PlayPages::mayWatch() for the games of a closed tournament. The services
 * ask again where it protects data (K14).
 */
final class TournamentAccess
{
    public function __construct(private readonly TournamentRepository $tournaments)
    {
    }

    public function mayCreate(User $user): bool
    {
        return $user->isAdmin() || $this->tournaments->canOrganise($user->id);
    }

    public function mayManage(Tournament $tournament, User $user): bool
    {
        if ($user->isAdmin() || $tournament->createdBy === $user->id) {
            return true;
        }

        return in_array($user->id, $this->tournaments->organiserIds($tournament->id), true);
    }

    public function isPlayer(Tournament $tournament, int $userId): bool
    {
        foreach ($this->tournaments->players($tournament->id) as $player) {
            if ($player->userId === $userId) {
                return true;
            }
        }

        return false;
    }

    /** May this member see the tournament page and watch its games (A106)? */
    public function maySee(Tournament $tournament, User $user): bool
    {
        if (!$tournament->isClosed()) {
            return true;
        }

        return $this->mayManage($tournament, $user) || $this->isPlayer($tournament, $user->id);
    }

    /**
     * For the game pages: may this member watch this game? True for games
     * outside tournaments (the normal rules apply then).
     */
    public function mayWatchGame(int $gameId, User $user): bool
    {
        $tournamentId = $this->tournaments->tournamentIdOfGame($gameId);
        if ($tournamentId === null) {
            return true;
        }
        $tournament = $this->tournaments->find($tournamentId);

        return $tournament === null || $this->maySee($tournament, $user);
    }
}
