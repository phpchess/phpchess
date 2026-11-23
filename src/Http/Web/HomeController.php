<?php

declare(strict_types=1);

namespace PhpChess\Http\Web;

use PhpChess\Domain\Play\Game;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Kernel\Services;
use PhpChess\Version;

/**
 * GET /: the start page.
 *
 * A welcome for visitors (log in, register) and members. For members:
 * the Play button and the games waiting for their move, each with a small
 * picture of the position, 8 to a page (?page=2 for the next 8).
 */
final class HomeController
{
    /** Games shown per page on the start page. */
    public const int GAMES_PER_PAGE = 8;

    public function __construct(private readonly Services $services)
    {
    }

    /** Shows the start page. */
    public function home(Request $request): Response
    {
        $translator = $this->services->translator();
        $variables = [
            'version' => Version::CURRENT,
            'registrationIsOpen' => $this->services->switches()->isOn('public_registration'),
            'waitingGames' => [],
            'usernames' => [],
            'page' => 1,
            'pageCount' => 1,
        ];

        $user = $this->services->authentication()->currentUser();
        if ($user !== null) {
            $games = $this->services->play()->gameRepository();
            $pageCount = max(1, (int) ceil($games->countWaitingFor($user->id) / self::GAMES_PER_PAGE));
            $page = min($pageCount, max(1, (int) $request->queryValue('page', '1')));
            $waitingGames = $games->listWaitingFor($user->id, self::GAMES_PER_PAGE, ($page - 1) * self::GAMES_PER_PAGE);
            $variables['waitingGames'] = $waitingGames;
            $variables['usernames'] = $this->usernamesOf($waitingGames);
            $variables['page'] = $page;
            $variables['pageCount'] = $pageCount;
        }

        return $this->services->pages()->render($request, 'home', $translator->text('home.title'), $variables);
    }

    /**
     * Names of the players in these games.
     *
     * @param list<Game> $games
     * @return array<int, string>
     */
    private function usernamesOf(array $games): array
    {
        $ids = [];
        foreach ($games as $game) {
            $ids[] = $game->whiteId;
            $ids[] = $game->blackId;
        }

        return $this->services->users()->usernamesOf($ids);
    }
}
