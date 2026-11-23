<?php

declare(strict_types=1);

namespace PhpChess\Http\Web\Account;

use PhpChess\Domain\ActionRefused;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Http\Web\MemberPages;
use PhpChess\Kernel\Services;

/**
 * My data (ID-11): download everything stored about me, or delete my account.
 *
 * Addresses (Routes.php), members only:
 *   GET  /account/data          the page, with both buttons
 *   GET  /account/data/export   the JSON file, as a download
 *   POST /account/delete        deletes the account (password needed)
 */
final class OwnDataController
{
    public function __construct(
        private readonly Services $services,
        private readonly MemberPages $pages,
    ) {
    }

    public function show(Request $request): Response
    {
        return $this->pages->render($request, 'account/data', 'own_data.title');
    }

    public function export(Request $request): Response
    {
        $user = $this->pages->user();
        $json = $this->services->ownData()->exportJson($user->id, $this->services->now);
        $fileName = 'phpchess-' . $user->username . '-' . gmdate('Y-m-d', $this->services->now) . '.json';

        return new Response(200, $json, [
            'Content-Type' => 'application/json; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function delete(Request $request): Response
    {
        $user = $this->pages->user();
        if ($request->formValue('confirm') !== 'yes') {
            return $this->pages->refused($request, '/account/data', new ActionRefused('own_data.confirm_needed'));
        }
        try {
            $this->services->ownData()->deleteAccount($user, $request->formValue('password'), $this->services->now);
        } catch (ActionRefused $refusal) {
            return $this->pages->refused($request, '/account/data', $refusal);
        }
        $this->services->authentication()->logOut();

        return $this->pages->done($request, '/', 'own_data.deleted');
    }
}
