<?php

declare(strict_types=1);

namespace PhpChess\Http\Web\Admin;

use PhpChess\Domain\Identity\User;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Kernel\Services;

/**
 * The admin start page, password confirmation and setup token (ID-08, INS-06, INS-07).
 *
 *   GET      /admin                the warnings and the admin menu
 *   GET/POST /admin/confirm        confirm the password before admin work (ID-08)
 *   POST     /admin/setup-token    a one-time link that reopens the installer for an update (INS-06)
 */
final class DashboardController
{
    public function __construct(
        private readonly Services $services,
        private readonly AdminPages $adminPages,
    ) {
    }

    public function dashboard(Request $request): Response
    {
        return $this->adminPages->render($request, 'admin/dashboard', 'admin.dashboard.title', [
            'pendingCount' => count($this->services->users()->listForAdmin('', User::STATE_PENDING, 500)),
        ]);
    }

    // --- Re-authentication (ID-08) ---

    public function showConfirm(Request $request): Response
    {
        return $this->services->pages()->render($request, 'admin/confirm', $this->adminPages->text('admin.confirm.title'), [
            'error' => '',
            'next' => self::adminPath($request->queryValue('next')),
        ]);
    }

    public function confirm(Request $request): Response
    {
        $admin = $this->adminPages->admin();
        $next = self::adminPath($request->formValue('next'));

        $allowed = $this->services->rateLimiter()->hit('admin-confirm:user:' . $admin->id, 5, 900, $this->services->now);
        if (!$allowed || !$this->services->loginService()->isPasswordRight($admin->id, $request->formValue('password'))) {
            return $this->services->pages()->render($request, 'admin/confirm', $this->adminPages->text('admin.confirm.title'), [
                'error' => $this->adminPages->text($allowed ? 'admin.confirm.wrong' : 'account.too_many_tries'),
                'next' => $next,
            ], 422);
        }

        $this->services->authentication()->confirmAdmin($this->services->now);

        return Response::redirect($request->basePath . $next);
    }

    /** Only an admin address may be the target after confirming. */
    private static function adminPath(string $path): string
    {
        return preg_match('#^/admin(/[A-Za-z0-9/_-]*)?$#', $path) === 1 ? $path : '/admin';
    }

    // --- Setup token (INS-06) ---

    public function createSetupToken(Request $request): Response
    {
        $token = $this->services->setupToken()->createSetupToken($this->services->now);
        $this->adminPages->audit($request, 'install.setup_token', 'A setup token for the installer was created.');

        return $this->adminPages->render($request, 'admin/setup_token', 'admin.setup_token.title', [
            'installerLink' => $request->baseUrl() . '/install?token=' . $token,
        ]);
    }
}
