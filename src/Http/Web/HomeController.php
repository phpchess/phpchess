<?php

declare(strict_types=1);

namespace PhpChess\Http\Web;

use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Http\Template;
use PhpChess\Version;

/**
 * GET /: the start page.
 *
 * In C1-1 a plain page confirming the install works. Chunk C1-2 (accounts,
 * look) turns it into the real start page.
 */
final class HomeController
{
    public function __construct(private readonly Template $template)
    {
    }

    /** Shows the start page. */
    public function home(Request $request): Response
    {
        return Response::html($this->template->render('home', [
            'basePath' => $request->basePath,
            'version' => Version::CURRENT,
        ]));
    }
}
