<?php

declare(strict_types=1);

namespace PhpChess\Http\Web\Admin;

use PhpChess\Domain\Identity\User;
use PhpChess\Domain\Tournament\Tournament;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Http\Web\Play\PlayPages;
use PhpChess\Kernel\Services;

/**
 * The admin's tournament page (ADM-06, A98, A107, A108).
 *
 *   GET  /admin/tournaments                      every tournament, the open disputes of all
 *                                                tournaments (with links to the games), and
 *                                                who may organise
 *   POST /admin/tournaments/organisers           allow a member to create tournaments
 *   POST /admin/tournaments/organisers/{user}/remove   take that right away
 *
 * An admin manages every tournament through its own desk
 * (/tournaments/{id}/manage): admins count as organisers everywhere.
 */
final class TournamentsAdminController
{
    public function __construct(
        private readonly Services $services,
        private readonly AdminPages $adminPages,
    ) {
    }

    public function show(Request $request): Response
    {
        $repository = $this->services->tournaments()->repository();
        $tournaments = $repository->listByStatus([
            Tournament::STATUS_REGISTRATION, Tournament::STATUS_RUNNING, Tournament::STATUS_FINISHED, Tournament::STATUS_CANCELLED,
        ], 200);
        $byId = [];
        foreach ($tournaments as $tournament) {
            $byId[$tournament->id] = $tournament;
        }
        $disputes = $repository->disputes(null, true);
        $boards = [];
        foreach ($disputes as $dispute) {
            $boards[$dispute['id']] = $repository->findPairing($dispute['pairingId']);
        }

        return $this->adminPages->render($request, 'admin/tournaments', 'admin.tournaments.title', [
            'tournaments' => $tournaments,
            'tournamentsById' => $byId,
            'disputes' => $disputes,
            'disputeBoards' => $boards,
            'names' => $this->services->users()->usernamesOf(array_map(fn (array $dispute): int => $dispute['raisedBy'], $disputes)),
            'organisers' => $repository->organiserNames(),
        ]);
    }

    public function addOrganiser(Request $request): Response
    {
        $user = $this->services->users()->findByUsername(trim($request->formValue('username')));
        if ($user === null || $user->state !== User::STATE_ACTIVE) {
            return $this->adminPages->redirect($request, '/admin/tournaments', 'error', 'tournament.player_unknown');
        }
        $this->services->tournaments()->repository()->setCanOrganise($user->id, true);
        $this->adminPages->audit($request, 'tournament.organiser', "{$user->username} may organise tournaments.");

        return $this->adminPages->redirect($request, '/admin/tournaments', 'success', 'admin.saved');
    }

    /**
     * @param array<string, string> $parts
     */
    public function removeOrganiser(Request $request, array $parts): Response
    {
        $userId = PlayPages::idFrom($parts, 'user');
        $this->services->tournaments()->repository()->setCanOrganise($userId, false);
        $this->adminPages->audit($request, 'tournament.organiser_removed', "User {$userId} may no longer organise tournaments.");

        return $this->adminPages->redirect($request, '/admin/tournaments', 'success', 'admin.saved');
    }
}
