<?php

declare(strict_types=1);

namespace PhpChess\Http\Web\Admin;

use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Kernel\Services;

/**
 * GET /admin/audit: the newest admin actions, who did them and from where (ADM-03).
 */
final class AuditController
{
    public function __construct(
        private readonly Services $services,
        private readonly AdminPages $adminPages,
    ) {
    }

    public function list(Request $request): Response
    {
        return $this->adminPages->render($request, 'admin/audit', 'admin.audit.title', [
            'entries' => $this->services->auditLog()->newest(),
        ]);
    }
}
