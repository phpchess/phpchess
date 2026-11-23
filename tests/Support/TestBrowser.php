<?php

declare(strict_types=1);

namespace PhpChess\Tests\Support;

use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Kernel\Kernel;
use PhpChess\Kernel\Session;

/**
 * A pretend browser for kernel and installer tests.
 *
 * Sends requests straight to a Kernel (no web server), keeps one session
 * between them like a browser's session cookie, keeps the other cookies the
 * responses set (remember-me), and remembers the CSRF token from the last
 * page, so a test can "fill in a form" with post().
 */
final class TestBrowser
{
    public Session $session;

    /** @var array<string, string> cookie name => value */
    public array $cookies = [];

    private string $lastCsrfToken = '';

    public function __construct(private readonly Kernel $kernel, private readonly string $basePath = '/chess')
    {
        $this->session = Session::inMemory();
    }

    /**
     * Opens a page.
     *
     * @param array<string, string> $query
     */
    public function get(string $path, array $query = []): Response
    {
        return $this->send(new Request('GET', $path, $this->basePath, query: $query, cookies: $this->cookies, host: 'example.test'));
    }

    /**
     * Sends a form, with the CSRF token of the last page unless one is given.
     *
     * @param array<string, string> $fields
     */
    public function post(string $path, array $fields = [], bool $withCsrfToken = true): Response
    {
        if ($withCsrfToken && !isset($fields['_csrf'])) {
            $fields['_csrf'] = $this->lastCsrfToken;
        }

        return $this->send(new Request('POST', $path, $this->basePath, form: $fields, cookies: $this->cookies, host: 'example.test'));
    }

    /**
     * Loads a page first, then sends its form: the usual way a person fills in a form.
     *
     * @param array<string, string> $fields
     */
    public function submit(string $pagePath, string $formPath, array $fields = []): Response
    {
        $this->get($pagePath);

        return $this->post($formPath, $fields);
    }

    private function send(Request $request): Response
    {
        $response = $this->kernel->handle($request, $this->session);
        if (preg_match('/name="_csrf" value="([0-9a-f]+)"/', $response->body, $found) === 1) {
            $this->lastCsrfToken = $found[1];
        }
        foreach ($response->cookies as $cookie) {
            if ($cookie->expiresAt !== 0 && $cookie->expiresAt < time()) {
                unset($this->cookies[$cookie->name]);
                continue;
            }
            $this->cookies[$cookie->name] = $cookie->value;
        }

        return $response;
    }
}
