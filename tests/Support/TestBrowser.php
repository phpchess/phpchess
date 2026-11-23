<?php

declare(strict_types=1);

namespace PhpChess\Tests\Support;

use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Http\UploadedFile;
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
     * Opens a page with extra request headers (names in lower case are added for you).
     *
     * @param array<string, string> $headers
     */
    public function getWithHeaders(string $path, array $headers): Response
    {
        return $this->send(new Request('GET', $path, $this->basePath, headers: self::lowerCaseNames($headers), cookies: $this->cookies, host: 'example.test'));
    }

    /**
     * Sends a form as the board script does: asking for a JSON answer.
     *
     * @param array<string, string> $fields
     */
    public function postJson(string $path, array $fields = []): Response
    {
        $fields['_csrf'] ??= $this->lastCsrfToken;

        return $this->send(new Request('POST', $path, $this->basePath, form: $fields, headers: ['accept' => 'application/json'], cookies: $this->cookies, host: 'example.test'));
    }

    /**
     * Sends a form with files, as a browser does with enctype="multipart/form-data".
     *
     * @param array<string, string>       $fields
     * @param array<string, UploadedFile> $files
     */
    public function postWithFiles(string $path, array $fields, array $files): Response
    {
        $fields['_csrf'] ??= $this->lastCsrfToken;

        return $this->send(new Request('POST', $path, $this->basePath, form: $fields, cookies: $this->cookies, host: 'example.test', files: $files));
    }

    /**
     * An app on the API: no cookies, no session, only "Authorization: Bearer <token>" (ID-10).
     *
     * @param array<string, string> $fields
     */
    public function apiWithToken(string $method, string $path, string $token, array $fields = []): Response
    {
        $request = new Request($method, $path, $this->basePath, form: $fields, headers: ['authorization' => 'Bearer ' . $token], host: 'example.test');

        return $this->kernel->handle($request, Session::inMemory());
    }

    /**
     * @param array<string, string> $headers
     * @return array<string, string>
     */
    private static function lowerCaseNames(array $headers): array
    {
        $lowerCased = [];
        foreach ($headers as $name => $value) {
            $lowerCased[strtolower($name)] = $value;
        }

        return $lowerCased;
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
