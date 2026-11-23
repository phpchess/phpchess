<?php

declare(strict_types=1);

namespace PhpChess\Http;

use PhpChess\Domain\Identity\Preferences;
use PhpChess\Domain\Identity\User;
use PhpChess\Domain\Language\Translator;
use PhpChess\Domain\Look\Skin;
use PhpChess\Kernel\Csrf;
use PhpChess\Kernel\Session;

/**
 * Renders a page of the site inside the shared layout (UI-03, UI-06).
 *
 * Every controller of the site uses this: it renders the page's own template,
 * then puts it in templates/layout.php with the header, the menu, the notices
 * ("flash messages") and the skin. Every template gets the same helpers:
 *
 *   $t          the Translator: e($t->text('login.title'))
 *   $basePath   the start of every link: <a href="<?= e($basePath) ?>/login">
 *   $csrfToken  for every form (K7)
 *   $user       the logged-in User, or null
 *   $template   to render a part: $template->render('partials/...', [...])
 *
 * Flash messages are short notices shown once on the next page, for example
 * "Your preferences are saved." after a redirect.
 */
final class PageRenderer
{
    private const string FLASH_KEY = 'flash_messages';

    /**
     * @param array{id: int, title: string, body: string}|null $serverMessage  the admin's server message (C-07)
     * @param array<string, bool>                              $menuSwitches   which switched features the menu shows
     */
    public function __construct(
        private readonly Template $template,
        private readonly Translator $translator,
        private readonly Session $session,
        private readonly Csrf $csrf,
        private readonly Skin $skin,
        private readonly string $siteName,
        private readonly ?User $user,
        private readonly Preferences $preferences,
        private readonly int $myMoveCount = 0,
        private readonly int $unreadMessageCount = 0,
        private readonly ?array $serverMessage = null,
        private readonly array $menuSwitches = [],
    ) {
    }

    /**
     * Renders one page.
     *
     * @param string               $templateName  for example "account/login"
     * @param string               $title         the page title, already translated
     * @param array<string, mixed> $variables     the template's own variables
     */
    public function render(Request $request, string $templateName, string $title, array $variables = [], int $status = 200): Response
    {
        $common = [
            't' => $this->translator,
            'basePath' => $request->basePath,
            'csrfToken' => $this->csrf->token(),
            'user' => $this->user,
            'title' => $title,
        ];
        $contentHtml = $this->template->render($templateName, $variables + $common);

        $layoutHtml = $this->template->render('layout', $common + [
            'contentHtml' => $contentHtml,
            'siteName' => $this->siteName,
            'skin' => $this->skin,
            'colourScheme' => $this->preferences->colourScheme,
            'boardTheme' => $this->preferences->boardTheme,
            'language' => $this->translator->language,
            'flashMessages' => $this->takeFlashMessages(),
            'currentPath' => $request->path,
            'myMoveCount' => $this->myMoveCount,
            'unreadMessageCount' => $this->unreadMessageCount,
            'menuSwitches' => $this->menuSwitches,
            'serverMessage' => $this->serverMessage,
            'isWide' => ($variables['isWide'] ?? false) === true,
        ]);

        return Response::html($layoutHtml, $status);
    }

    /** A redirect with a notice shown on the next page ("success" or "error"). */
    public function redirectWithMessage(Request $request, string $path, string $type, string $message): Response
    {
        $this->addFlashMessage($type, $message);

        return Response::redirect($request->basePath . $path);
    }

    /** Keeps a notice for the next page. */
    public function addFlashMessage(string $type, string $message): void
    {
        $messages = $this->session->get(self::FLASH_KEY);
        $messages = is_array($messages) ? $messages : [];
        $messages[] = ['type' => $type === 'error' ? 'error' : 'success', 'text' => $message];
        $this->session->set(self::FLASH_KEY, $messages);
    }

    /**
     * @return list<array{type: string, text: string}>
     */
    private function takeFlashMessages(): array
    {
        $stored = $this->session->get(self::FLASH_KEY);
        $this->session->remove(self::FLASH_KEY);
        if (!is_array($stored)) {
            return [];
        }

        $messages = [];
        foreach ($stored as $message) {
            if (is_array($message) && is_string($message['type'] ?? null) && is_string($message['text'] ?? null)) {
                $messages[] = ['type' => $message['type'], 'text' => $message['text']];
            }
        }

        return $messages;
    }
}
