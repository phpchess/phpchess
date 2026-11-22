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
 * between them like a browser's cookie, and remembers the CSRF token from the
 * last page, so a test can "fill in a form" with post().
 */
final class TestBrowser
{
    public Session $session;
    private string $lastCsrfToken = '';

    public function __construct(private readonly Kernel $kernel, private readonly string $basePath = '/chess')
    {
        $this->session = Session::inMemory();
    }

    /** Opens a page. */
    public function get(string $path): Response
    {
        return $this->send(new Request('GET', $path, $this->basePath, host: 'example.test'));
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

        return $this->send(new Request('POST', $path, $this->basePath, form: $fields, host: 'example.test'));
    }

    private function send(Request $request): Response
    {
        $response = $this->kernel->handle($request, $this->session);
        if (preg_match('/name="_csrf" value="([0-9a-f]+)"/', $response->body, $found) === 1) {
            $this->lastCsrfToken = $found[1];
        }

        return $response;
    }
}
