<?php

declare(strict_types=1);

namespace PhpChess\Kernel;

use PhpChess\Http\Response;

/**
 * The security headers sent with every response (ARCHITECTURE.md §5, contract K9).
 *
 * - Content-Security-Policy: the browser loads scripts, styles, images, fonts
 *   and connections from this site only ("self"), so phpChess makes no outside
 *   requests (A16, INS-10) and injected script tags cannot run.
 *   "worker-src blob:" is not needed: the engine worker is a local file.
 * - frame-ancestors 'none' / X-Frame-Options: no other site may frame the pages.
 * - nosniff, Referrer-Policy, and HSTS when the request came over HTTPS.
 *
 * The two outside exceptions of K9 (Lichess, Web Push) are added by their own
 * chunks, behind their switches.
 */
final class SecurityHeaders
{
    public const string CONTENT_SECURITY_POLICY =
        "default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; "
        . "font-src 'self'; connect-src 'self'; worker-src 'self'; manifest-src 'self'; "
        . "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'";

    /** Returns a copy of the response with all security headers added. */
    public static function addTo(Response $response, bool $isHttps): Response
    {
        $response = $response
            ->withHeader('Content-Security-Policy', self::CONTENT_SECURITY_POLICY)
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('Referrer-Policy', 'same-origin')
            ->withHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if ($isHttps) {
            $response = $response->withHeader('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }
}
