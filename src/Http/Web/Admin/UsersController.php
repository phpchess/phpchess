<?php

declare(strict_types=1);

namespace PhpChess\Http\Web\Admin;

use PhpChess\Domain\Identity\AccountRefused;
use PhpChess\Domain\Identity\PasswordRules;
use PhpChess\Domain\Identity\User;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Kernel\Services;

/**
 * The admin's user administration (ADM-01, ID-03, ID-07, ID-08, ID-09).
 *
 *   GET  /admin/users                     list, search, filter by state
 *   POST /admin/users/{id}/approve        approve a pending registration (ID-03)
 *   POST /admin/users/{id}/state          active, disabled or archived (ID-07)
 *   POST /admin/users/{id}/role           admin, teacher, student or player (ID-08)
 *   POST /admin/users/{id}/rename         a new user name; the id stays (ID-09)
 *
 * Every change is written to the audit log. Two guards keep the server
 * manageable: an admin can't block or demote themselves, and the last active
 * admin can't be blocked or demoted.
 */
final class UsersController
{
    public function __construct(
        private readonly Services $services,
        private readonly AdminPages $adminPages,
    ) {
    }

    public function list(Request $request): Response
    {
        $search = trim($request->queryValue('search'));
        $state = $request->queryValue('state');

        return $this->adminPages->render($request, 'admin/users', 'admin.users.title', [
            'users' => $this->services->users()->listForAdmin($search, $state),
            'search' => $search,
            'stateFilter' => in_array($state, User::STATES, true) ? $state : '',
            'states' => User::STATES,
            'roles' => User::ROLES,
        ]);
    }

    /**
     * @param array<string, string> $pathParts
     */
    public function approve(Request $request, array $pathParts): Response
    {
        $user = $this->findUser($pathParts);
        if ($user === null) {
            return $this->backWithError($request, 'admin.users.not_found');
        }

        $this->services->accountService()->approve($user->id);
        $this->adminPages->audit($request, 'user.approve', "Approved {$user->username} (#{$user->id}).");

        return $this->adminPages->redirect($request, '/admin/users', 'success', 'admin.users.saved');
    }

    /**
     * @param array<string, string> $pathParts
     */
    public function changeState(Request $request, array $pathParts): Response
    {
        $user = $this->findUser($pathParts);
        $newState = $request->formValue('state');
        if ($user === null || !in_array($newState, [User::STATE_ACTIVE, User::STATE_DISABLED, User::STATE_ARCHIVED], true)) {
            return $this->backWithError($request, 'admin.users.not_found');
        }
        if ($newState !== User::STATE_ACTIVE && $this->wouldLockOutAdmins($user)) {
            return $this->backWithError($request, 'admin.users.keep_one_admin');
        }

        $this->services->users()->setState($user->id, $newState);
        if ($newState !== User::STATE_ACTIVE) {
            $this->services->loginService()->forgetAllDevices($user->id);
        }
        $this->adminPages->audit($request, 'user.state', "{$user->username} (#{$user->id}): {$user->state} -> {$newState}.");

        return $this->adminPages->redirect($request, '/admin/users', 'success', 'admin.users.saved');
    }

    /**
     * @param array<string, string> $pathParts
     */
    public function changeRole(Request $request, array $pathParts): Response
    {
        $user = $this->findUser($pathParts);
        $newRole = $request->formValue('role');
        if ($user === null || !in_array($newRole, User::ROLES, true)) {
            return $this->backWithError($request, 'admin.users.not_found');
        }
        if ($newRole !== User::ROLE_ADMIN && $user->isAdmin() && $this->wouldLockOutAdmins($user)) {
            return $this->backWithError($request, 'admin.users.keep_one_admin');
        }

        $this->services->users()->setRole($user->id, $newRole);
        $this->adminPages->audit($request, 'user.role', "{$user->username} (#{$user->id}): {$user->role} -> {$newRole}.");

        return $this->adminPages->redirect($request, '/admin/users', 'success', 'admin.users.saved');
    }

    /**
     * @param array<string, string> $pathParts
     */
    public function rename(Request $request, array $pathParts): Response
    {
        $user = $this->findUser($pathParts);
        $newName = trim($request->formValue('username'));
        if ($user === null) {
            return $this->backWithError($request, 'admin.users.not_found');
        }

        try {
            PasswordRules::checkUsername($newName);
            if (strcasecmp($newName, $user->username) !== 0 && $this->services->users()->usernameExists($newName)) {
                throw new AccountRefused('register.username_taken');
            }
        } catch (AccountRefused $refusal) {
            return $this->backWithError($request, $refusal->languageKey);
        }

        $this->services->users()->rename($user->id, $newName);
        $this->adminPages->audit($request, 'user.rename', "#{$user->id}: {$user->username} -> {$newName}.");

        return $this->adminPages->redirect($request, '/admin/users', 'success', 'admin.users.saved');
    }

    // --- Helpers ---

    /**
     * @param array<string, string> $pathParts
     */
    private function findUser(array $pathParts): ?User
    {
        $id = $pathParts['id'] ?? '';

        return ctype_digit($id) ? $this->services->users()->findById((int) $id) : null;
    }

    /** True when the change would take away the admin's own rights, or leave no active admin. */
    private function wouldLockOutAdmins(User $user): bool
    {
        if ($user->id === $this->adminPages->admin()->id) {
            return true;
        }

        return $user->isAdmin() && $user->isActive() && $this->services->users()->countActiveAdmins() <= 1;
    }

    private function backWithError(Request $request, string $messageKey): Response
    {
        return $this->adminPages->redirect($request, '/admin/users', 'error', $messageKey);
    }
}
