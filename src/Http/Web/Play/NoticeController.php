<?php

declare(strict_types=1);

namespace PhpChess\Http\Web\Play;

use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Kernel\Services;

/**
 * In-app notices (N-01): the list behind the badge in the menu.
 *
 * Addresses (Routes.php), members only:
 *   GET  /notices           the newest notices, unread ones marked
 *   POST /notices/read-all  marks them all as read
 * Opening a game marks its notices as read too (GameController).
 */
final class NoticeController
{
    public function __construct(
        private readonly Services $services,
        private readonly PlayPages $pages,
    ) {
    }

    public function list(Request $request): Response
    {
        $user = $this->pages->user();

        return $this->services->pages()->render($request, 'play/notices', $this->pages->text('notices.title'), [
            'notices' => $this->services->play()->noticeRepository()->newestFor($user->id),
        ]);
    }

    public function markAllRead(Request $request): Response
    {
        $this->services->play()->noticeRepository()->markAllRead($this->pages->user()->id, $this->services->now);

        return Response::redirect($request->basePath . '/notices');
    }
}
