<?php

declare(strict_types=1);

namespace PhpChess\Domain\Identity;

use PhpChess\Domain\ActionRefused;
use PhpChess\Domain\Community\Avatars;
use PhpChess\Domain\Play\Game;
use PhpChess\Domain\Play\Games;
use PhpChess\Infra\Identity\OwnDataRepository;
use PhpChess\Infra\Identity\UserRepository;
use PhpChess\Infra\Play\GameRepository;

/**
 * The member's own data (ID-11): a full export, and deleting the account.
 *
 * The export is one JSON file with every row stored about the member
 * (OwnDataRepository lists the tables); secrets such as password hashes
 * are left out. Deleting asks for the password, resigns the running games
 * (so the opponents are not left waiting), removes the uploaded avatar and
 * every personal row, and turns the account into "deleted-<id>" with no
 * email, so the finished games stay for the opponents without a name.
 * The last active admin can't delete their account.
 */
final class OwnData
{
    public function __construct(
        private readonly OwnDataRepository $ownData,
        private readonly UserRepository $users,
        private readonly GameRepository $gameRepository,
        private readonly Games $games,
        private readonly Avatars $avatars,
    ) {
    }

    /** Everything stored about the member, as pretty JSON. */
    public function exportJson(int $userId, int $now): string
    {
        $data = ['exported_at' => gmdate('Y-m-d H:i:s', $now) . ' UTC'] + $this->ownData->export($userId);

        return (string) json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /** Deletes the account after checking the password; throws ActionRefused otherwise. */
    public function deleteAccount(User $user, string $password, int $now): void
    {
        if (!password_verify($password, $this->users->passwordHashFor($user->id))) {
            throw new ActionRefused('own_data.wrong_password');
        }
        if ($user->isAdmin() && $this->users->countActiveAdmins() <= 1) {
            throw new ActionRefused('own_data.last_admin');
        }

        foreach ($this->gameRepository->listForPlayer($user->id, Game::STATE_RUNNING) as $game) {
            $this->games->resign($game->id, $user->id, $now);
        }
        $this->avatars->deleteUpload($user->id);
        $this->ownData->deleteAndAnonymise($user->id);
    }
}
