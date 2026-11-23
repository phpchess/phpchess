<?php

declare(strict_types=1);

namespace PhpChess\Http\Web;

use LogicException;
use PhpChess\Domain\ActionRefused;
use PhpChess\Domain\Identity\User;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Kernel\Services;

/**
 * Helpers shared by the C2-2 pages (community, school, own data, devices):
 * the logged-in member, texts, redirects with a notice, and reading ids
 * and names from the address. PlayPages does the same for the play pages.
 */
final class MemberPages
{
    public function __construct(private readonly Services $services)
    {
    }

    /** The logged-in user; these routes are for members only, so there always is one. */
    public function user(): User
    {
        $user = $this->services->authentication()->currentUser();
        if ($user === null) {
            throw new LogicException('A members-only route ran without a logged-in user.');
        }

        return $user;
    }

    /**
     * @param array<string, string|int> $values
     */
    public function text(string $key, array $values = []): string
    {
        return $this->services->translator()->text($key, $values);
    }

    /**
     * Shows a page.
     *
     * @param array<string, mixed> $variables
     */
    public function render(Request $request, string $template, string $titleKey, array $variables = [], int $status = 200): Response
    {
        return $this->services->pages()->render($request, $template, $this->text($titleKey), $variables, $status);
    }

    /**
     * Back to a page with a success notice.
     *
     * @param array<string, string|int> $values
     */
    public function done(Request $request, string $path, string $messageKey, array $values = []): Response
    {
        return $this->services->pages()->redirectWithMessage($request, $path, 'success', $this->text($messageKey, $values));
    }

    /** Back to a page with the refusal's text as an error notice. */
    public function refused(Request $request, string $path, ActionRefused $refusal): Response
    {
        return $this->services->pages()->redirectWithMessage($request, $path, 'error', $this->text($refusal->languageKey, $refusal->values));
    }

    /** The plain "not found" page, used for anything missing or hidden alike. */
    public function notFound(Request $request): Response
    {
        return $this->render($request, 'play/message', 'page.not_found', ['message' => $this->text('page.not_found')], 404);
    }

    /**
     * A number from the address, such as the 12 of /school/classes/12; 0 when it isn't one.
     *
     * @param array<string, string> $parts
     */
    public static function idFrom(array $parts, string $name = 'id'): int
    {
        $value = $parts[$name] ?? '';

        return preg_match('/^[1-9][0-9]{0,9}$/', $value) === 1 ? (int) $value : 0;
    }

    /**
     * A user name from the address (already URL-decoded by the router), or '' when it can't be one.
     *
     * @param array<string, string> $parts
     */
    public static function nameFrom(array $parts, string $name = 'name'): string
    {
        $value = rawurldecode($parts[$name] ?? '');

        return preg_match('/^[A-Za-z0-9_.-]{1,40}$/', $value) === 1 ? $value : '';
    }
}
