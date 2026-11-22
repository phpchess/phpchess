<?php

declare(strict_types=1);

namespace PhpChess\Http;

/**
 * The one error format of the JSON API (API-04).
 *
 * Every API refusal, from any endpoint, looks the same:
 *
 *     { "error": { "code": "not_found", "message": "No such endpoint." } }
 *
 * "code" is a fixed word a program can test; "message" is for people.
 * The HTTP status says the kind of error as usual (404, 405, 419, 429, 500).
 */
final class ApiError
{
    /** Builds the error response. */
    public static function response(int $status, string $code, string $message): Response
    {
        return Response::json(['error' => ['code' => $code, 'message' => $message]], $status);
    }
}
