<?php

declare(strict_types=1);

namespace PhpChess\Kernel;

use PhpChess\Http\Request;

/**
 * Protection against cross-site request forgery (contract K7).
 *
 * Every session gets one random token. Pages put it into every form as the
 * hidden field "_csrf"; JavaScript sends it in the "X-CSRF-Token" header.
 * The Kernel checks it on every POST before any controller runs. A site that
 * tricks a visitor's browser into posting to phpChess cannot know the token.
 */
final class Csrf
{
    public const string FORM_FIELD = '_csrf';
    public const string HEADER = 'X-CSRF-Token';

    private const string SESSION_KEY = 'csrf_token';

    public function __construct(private readonly Session $session)
    {
    }

    /** The session's token, created on first use (64 hex characters, 256 bits). */
    public function token(): string
    {
        $token = $this->session->getString(self::SESSION_KEY);
        if ($token === '') {
            $token = bin2hex(random_bytes(32));
            $this->session->set(self::SESSION_KEY, $token);
        }

        return $token;
    }

    /** True when the request carries this session's token, in the form or the header. */
    public function isValidRequest(Request $request): bool
    {
        $sentToken = $request->formValue(self::FORM_FIELD);
        if ($sentToken === '') {
            $sentToken = $request->header(self::HEADER);
        }

        $expectedToken = $this->session->getString(self::SESSION_KEY);
        if ($expectedToken === '' || $sentToken === '') {
            return false;
        }

        // hash_equals takes the same time whatever the input, so the token
        // cannot be guessed one character at a time by measuring.
        return hash_equals($expectedToken, $sentToken);
    }
}
