<?php

declare(strict_types=1);

namespace PhpChess\Http\Web\Admin;

use PhpChess\Domain\Identity\User;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Kernel\Services;
use LogicException;

/**
 * What every admin page shares: the warning banner, the admin menu, the
 * logged-in admin and the audit log (INS-07, ADM-01 to ADM-03).
 *
 * Used by every controller in Http/Web/Admin. render() adds the banner's
 * warnings (UnsafeStateCheck) to every admin page; audit() records an admin
 * action with who did it and from where.
 */
final class AdminPages
{
    public function __construct(private readonly Services $services)
    {
    }

    /**
     * Renders an admin page with the warning banner and the admin menu.
     *
     * @param array<string, mixed> $variables
     */
    public function render(Request $request, string $templateName, string $titleKey, array $variables = [], int $status = 200): Response
    {
        $warnings = $this->services->unsafeStateCheck()->warnings($request->isHttps, $this->services->now);

        return $this->services->pages()->render($request, $templateName, $this->text($titleKey), $variables + [
            'warnings' => $warnings,
            'currentAdminPath' => $request->path,
        ], $status);
    }

    /** Back to an admin page with a notice. */
    public function redirect(Request $request, string $path, string $type, string $messageKey): Response
    {
        return $this->services->pages()->redirectWithMessage($request, $path, $type, $this->text($messageKey));
    }

    /** Records one admin action in the audit log (ADM-03). */
    public function audit(Request $request, string $action, string $details): void
    {
        $this->services->auditLog()->record($this->admin()->id, $action, $details, $request->clientIp);
    }

    /** The logged-in admin; admin routes always have one. */
    public function admin(): User
    {
        $user = $this->services->authentication()->currentUser();
        if ($user === null || !$user->isAdmin()) {
            throw new LogicException('An admin route ran without a logged-in admin.');
        }

        return $user;
    }

    public function text(string $key): string
    {
        return $this->services->translator()->text($key);
    }
}
