<?php

declare(strict_types=1);

namespace PhpChess\Http;

/**
 * One HTTP response: status code, headers and body (ARCHITECTURE.md §5).
 *
 * Contract K13: every controller returns a Response, including redirects and
 * refusals. Nothing is printed and nothing runs after the decision is made;
 * the kernel sends the returned object once, at the very end.
 *
 * A Response is never changed in place: withHeader() returns a copy, so a
 * response passed around cannot be altered by surprise.
 */
final class Response
{
    /**
     * @param array<string, string> $headers  header name => value
     * @param list<Cookie>          $cookies  cookies to set or remove
     */
    public function __construct(
        public readonly int $status = 200,
        public readonly string $body = '',
        public readonly array $headers = [],
        public readonly array $cookies = [],
    ) {
    }

    // --- The kinds of response ---

    /** An HTML page. */
    public static function html(string $body, int $status = 200): self
    {
        return new self($status, $body, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * A JSON answer, used by the API (API-01).
     *
     * @param array<mixed> $data
     */
    public static function json(array $data, int $status = 200): self
    {
        $body = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return new self($status, $body, ['Content-Type' => 'application/json; charset=utf-8']);
    }

    /** A redirect to another address (303: the browser follows with GET). */
    public static function redirect(string $url, int $status = 303): self
    {
        return new self($status, '', ['Location' => $url]);
    }

    /** A plain-text answer, for example from the job runner. */
    public static function text(string $body, int $status = 200): self
    {
        return new self($status, $body, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    // --- Changing a copy ---

    /** Returns a copy with one header added or replaced. */
    public function withHeader(string $name, string $value): self
    {
        $headers = $this->headers;
        $headers[$name] = $value;

        return new self($this->status, $this->body, $headers, $this->cookies);
    }

    /** Returns a copy that also sets (or removes) one cookie. */
    public function withCookie(Cookie $cookie): self
    {
        $cookies = $this->cookies;
        $cookies[] = $cookie;

        return new self($this->status, $this->body, $this->headers, $cookies);
    }

    /** The cookie of this name set by this response, or null. */
    public function cookie(string $name): ?Cookie
    {
        foreach ($this->cookies as $cookie) {
            if ($cookie->name === $name) {
                return $cookie;
            }
        }

        return null;
    }

    /** The value of one header, or "" when it is not set. */
    public function header(string $name): string
    {
        return $this->headers[$name] ?? '';
    }

    // --- Sending ---

    /** Sends status, headers and body to the browser. Called once, by public/index.php. */
    public function send(): void
    {
        http_response_code($this->status);
        // Don't tell strangers which PHP version runs the site (found by the ZAP scan, A27).
        header_remove('X-Powered-By');
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
        foreach ($this->cookies as $cookie) {
            setcookie($cookie->name, $cookie->value, [
                'expires' => $cookie->expiresAt,
                'path' => $cookie->path,
                'secure' => $cookie->secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        echo $this->body;
    }
}
