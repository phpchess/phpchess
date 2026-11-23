<?php

declare(strict_types=1);

namespace PhpChess\Http\Web\Admin;

use PhpChess\Domain\Community\GameChat;
use PhpChess\Domain\Community\WordFilter;
use PhpChess\Domain\System\Archive;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Infra\Content\ContentRepository;
use PhpChess\Infra\Jobs\NewsletterJob;
use PhpChess\Kernel\Services;

/**
 * The admin's community pages (ADM-05, ADM-07, C-05, G-12, A44).
 *
 * Addresses (Routes.php), admins only:
 *   GET  /admin/content               news, server messages, tips, FAQ and pages
 *   POST /admin/content               add an item
 *   POST /admin/content/{id}          change an item
 *   POST /admin/content/{id}/delete   delete an item
 *   GET  /admin/community             word filter, game chat kind, newsletter speed, archive cut-off
 *   POST /admin/community             save them
 *   GET  /admin/newsletter            write a newsletter; earlier ones with their progress
 *   POST /admin/newsletter            queue it for every member with an email address
 *   GET  /admin/notices?user=NAME     search the stored notices (A44)
 * Every change is written to the audit log.
 */
final class CommunityAdminController
{
    public function __construct(
        private readonly Services $services,
        private readonly AdminPages $pages,
    ) {
    }

    // --- Content (ADM-05) ---

    public function content(Request $request): Response
    {
        $items = [];
        foreach (ContentRepository::KINDS as $kind) {
            $items[$kind] = $this->services->content()->listKind($kind, false);
        }

        return $this->pages->render($request, 'admin/content', 'admin.content.title', [
            'itemsByKind' => $items,
            'kinds' => ContentRepository::KINDS,
        ]);
    }

    public function createContent(Request $request): Response
    {
        $kind = $request->formValue('kind');
        $title = trim($request->formValue('title'));
        if (!in_array($kind, ContentRepository::KINDS, true) || $title === '') {
            return $this->pages->redirect($request, '/admin/content', 'error', 'admin.content.title_needed');
        }
        $slug = self::slugFrom($request->formValue('slug'), $title);
        $this->services->content()->create(
            $kind,
            $kind === ContentRepository::PAGE ? $slug : '',
            mb_substr($title, 0, 200),
            $request->formValue('body'),
            $request->formValue('published') === 'yes',
            (int) $request->formValue('sort_order', '0'),
        );
        $this->pages->audit($request, 'content.create', $kind . ': ' . $title);

        return $this->pages->redirect($request, '/admin/content', 'success', 'admin.content.saved');
    }

    /**
     * @param array<string, string> $parts
     */
    public function updateContent(Request $request, array $parts): Response
    {
        $item = $this->services->content()->find((int) ($parts['id'] ?? 0));
        $title = trim($request->formValue('title'));
        if ($item === null || $title === '') {
            return $this->pages->redirect($request, '/admin/content', 'error', 'admin.content.title_needed');
        }
        $this->services->content()->update(
            $item['id'],
            $item['kind'] === ContentRepository::PAGE ? self::slugFrom($request->formValue('slug'), $title) : '',
            mb_substr($title, 0, 200),
            $request->formValue('body'),
            $request->formValue('published') === 'yes',
            (int) $request->formValue('sort_order', '0'),
        );
        $this->pages->audit($request, 'content.update', $item['kind'] . ': ' . $title);

        return $this->pages->redirect($request, '/admin/content', 'success', 'admin.content.saved');
    }

    /**
     * @param array<string, string> $parts
     */
    public function deleteContent(Request $request, array $parts): Response
    {
        $item = $this->services->content()->find((int) ($parts['id'] ?? 0));
        if ($item !== null) {
            $this->services->content()->delete($item['id']);
            $this->pages->audit($request, 'content.delete', $item['kind'] . ': ' . $item['title']);
        }

        return $this->pages->redirect($request, '/admin/content', 'success', 'admin.content.deleted');
    }

    // --- Community settings (C-05, G-12, ADM-07, A43) ---

    public function settings(Request $request): Response
    {
        $settings = $this->services->settings();

        return $this->pages->render($request, 'admin/community', 'admin.community.title', [
            'wordFilter' => $settings->getString(WordFilter::SETTING),
            'chatMode' => $this->services->gameChat()->mode(),
            'chatModes' => GameChat::MODES,
            'newsletterPerRun' => $settings->getInt('newsletter.per_run', NewsletterJob::DEFAULT_PER_RUN),
            'archiveCutoffMs' => $this->services->archive()->cutoffMs(),
        ]);
    }

    public function saveSettings(Request $request): Response
    {
        $settings = $this->services->settings();
        $settings->set(WordFilter::SETTING, mb_substr($request->formValue('word_filter'), 0, 10000));
        $chatMode = $request->formValue('chat_mode');
        if (in_array($chatMode, GameChat::MODES, true)) {
            $settings->set('game_chat.mode', $chatMode);
        }
        $settings->set('newsletter.per_run', max(1, min(1000, (int) $request->formValue('newsletter_per_run', (string) NewsletterJob::DEFAULT_PER_RUN))));
        $settings->set('archive.cutoff_ms', max(50, min(60000, (int) $request->formValue('archive_cutoff_ms', (string) Archive::DEFAULT_CUTOFF_MS))));
        $this->pages->audit($request, 'community.settings', 'word filter, chat, newsletter and archive settings saved');

        return $this->pages->redirect($request, '/admin/community', 'success', 'admin.community.saved');
    }

    // --- Newsletter (ADM-07) ---

    public function newsletter(Request $request): Response
    {
        return $this->pages->render($request, 'admin/newsletter', 'admin.newsletter.title', [
            'newsletters' => $this->services->newsletters()->listAll(),
        ]);
    }

    public function sendNewsletter(Request $request): Response
    {
        $subject = trim($request->formValue('subject'));
        $body = trim($request->formValue('body'));
        if ($subject === '' || $body === '') {
            return $this->pages->redirect($request, '/admin/newsletter', 'error', 'admin.newsletter.needs_text');
        }
        $queued = $this->services->newsletters()->createAndQueue(mb_substr($subject, 0, 200), $body, $this->pages->admin()->id, $this->services->now);
        $this->pages->audit($request, 'newsletter.queue', $subject . " ({$queued} recipients)");

        return $this->services->pages()->redirectWithMessage(
            $request,
            '/admin/newsletter',
            'success',
            $this->services->translator()->text('admin.newsletter.queued', ['count' => $queued]),
        );
    }

    // --- Stored notices, for searches (A44) ---

    public function notices(Request $request): Response
    {
        $username = trim($request->queryValue('user'));
        $userId = null;
        if ($username !== '') {
            $member = $this->services->users()->findByUsername($username);
            // An unknown name finds nothing (id 0), rather than everybody.
            $userId = $member === null ? 0 : $member->id;
        }

        return $this->pages->render($request, 'admin/notices', 'admin.notices.title', [
            'notices' => $this->services->play()->noticeRepository()->searchForAdmin($userId),
            'username' => $username,
        ]);
    }

    /** A page address name: lower-case letters, digits and "-", from the typed one or the title. */
    private static function slugFrom(string $typed, string $title): string
    {
        $source = trim($typed) !== '' ? $typed : $title;
        $slug = strtolower(trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', $source), '-'));

        return substr($slug === '' ? 'page' : $slug, 0, 60);
    }
}
