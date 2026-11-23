<?php

declare(strict_types=1);

namespace PhpChess\Http\Web;

use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Kernel\Services;
use PhpChess\Version;

/**
 * GET /: the start page.
 *
 * A welcome for visitors (log in, register) and members. Chunk C2-1 adds
 * the games: one tap to start, the list of running games.
 */
final class HomeController
{
    public function __construct(private readonly Services $services)
    {
    }

    /** Shows the start page. */
    public function home(Request $request): Response
    {
        $translator = $this->services->translator();

        return $this->services->pages()->render($request, 'home', $translator->text('home.title'), [
            'version' => Version::CURRENT,
            'registrationIsOpen' => $this->services->switches()->isOn('public_registration'),
        ]);
    }
}
