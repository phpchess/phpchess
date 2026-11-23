<?php

declare(strict_types=1);

namespace PhpChess\Http\Web;

use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Infra\Content\ContentRepository;
use PhpChess\Kernel\Services;

/**
 * The admin's content, for everyone (C-06, C-08, C-09). Server messages
 * (C-07) are shown on every page by the layout; news and the tip of the
 * day on the start page (HomeController).
 *
 * Addresses (Routes.php):
 *   GET /faq            the FAQ
 *   GET /news           all news, newest first
 *   GET /pages/{slug}   a static page, such as /pages/rules
 */
final class ContentController
{
    public function __construct(private readonly Services $services)
    {
    }

    public function faq(Request $request): Response
    {
        return $this->services->pages()->render($request, 'content/faq', $this->services->translator()->text('content.faq'), [
            'entries' => $this->services->content()->listKind(ContentRepository::FAQ, true),
        ]);
    }

    public function news(Request $request): Response
    {
        return $this->services->pages()->render($request, 'content/news', $this->services->translator()->text('content.news'), [
            'news' => $this->services->content()->listKind(ContentRepository::NEWS, true, 50),
        ]);
    }

    /**
     * @param array<string, string> $parts
     */
    public function page(Request $request, array $parts): Response
    {
        $page = $this->services->content()->findPage($parts['slug'] ?? '');
        if ($page === null) {
            $text = $this->services->translator()->text('page.not_found');

            return $this->services->pages()->render($request, 'play/message', $text, ['message' => $text], 404);
        }

        return $this->services->pages()->render($request, 'content/page', $page['title'], ['page' => $page]);
    }

    /**
     * The tip of the day (C-08): the same tip all day, the next one tomorrow.
     *
     * @return array{id: int, kind: string, slug: string, title: string, body: string, is_published: bool, sort_order: int, created_at: string}|null
     */
    public static function tipOfTheDay(ContentRepository $content, int $now): ?array
    {
        $tips = $content->listKind(ContentRepository::TIP, true);
        if ($tips === []) {
            return null;
        }

        return $tips[intdiv($now, 86400) % count($tips)];
    }
}
