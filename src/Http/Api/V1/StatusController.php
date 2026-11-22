<?php

declare(strict_types=1);

namespace PhpChess\Http\Api\V1;

use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Version;

/**
 * GET /api/v1/status: the API skeleton's first endpoint (API-01).
 *
 * Says that the server is up and which version it runs. The web client and
 * outside programs use it to check they are talking to a phpChess 6 server.
 * Reads only, so it is a GET (K7).
 */
final class StatusController
{
    /** Returns the server's name, version and API version as JSON. */
    public function status(Request $request): Response
    {
        return Response::json([
            'server' => 'phpChess',
            'version' => Version::CURRENT,
            'api' => 1,
        ]);
    }
}
