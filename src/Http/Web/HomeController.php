<?php

declare(strict_types=1);

namespace PhpChess\Http\Web;

use PhpChess\Domain\Play\Challenge;
use PhpChess\Domain\Play\Game;
use PhpChess\Domain\System\FeatureSwitches;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Infra\Content\ContentRepository;
use PhpChess\Kernel\Services;
use PhpChess\Version;

/**
 * GET /: the start page.
 *
 * Visitors: a welcome, log in and register, the news and the tip of the day.
 * Members get the dashboard (A44), a wider page in two columns:
 *   left   tiles with mini boards: "My move" (8 to a page, ?page=2 for
 *          the next 8), then "My games" where the opponent is to move;
 *   right  text lists: challenges for me, open challenges (one-tap accept),
 *          live games to watch, my 10 most recent finished games;
 *   below  the tip of the day and the news.
 * On a phone it is one column in that order (community.css).
 * The class rule hides challenges from outside a student's class (SCH-05).
 */
final class HomeController
{
    /** Games shown per page on the start page. */
    public const int GAMES_PER_PAGE = 8;

    public const int OTHER_GAMES = 8;
    public const int LIST_LENGTH = 10;

    public function __construct(private readonly Services $services)
    {
    }

    /** Shows the start page. */
    public function home(Request $request): Response
    {
        $translator = $this->services->translator();
        $content = $this->services->content();
        $variables = [
            'version' => Version::CURRENT,
            'registrationIsOpen' => $this->services->switches()->isOn(FeatureSwitches::PUBLIC_REGISTRATION),
            'news' => $content->listKind(ContentRepository::NEWS, true, 3),
            'tip' => ContentController::tipOfTheDay($content, $this->services->now),
        ];

        $user = $this->services->authentication()->currentUser();
        if ($user !== null) {
            $variables += $this->dashboard($request, $user->id);
            $variables['isWide'] = true;
        }

        return $this->services->pages()->render($request, 'home', $translator->text('home.title'), $variables);
    }

    /**
     * Everything on the member's dashboard (A44).
     *
     * @return array<string, mixed>
     */
    private function dashboard(Request $request, int $userId): array
    {
        $now = $this->services->now;
        $games = $this->services->play()->gameRepository();
        $challenges = $this->services->play()->challengeRepository();
        $rules = $this->services->play()->classRules();

        $pageCount = max(1, (int) ceil($games->countWaitingFor($userId) / self::GAMES_PER_PAGE));
        $page = min($pageCount, max(1, (int) $request->queryValue('page', '1')));
        $myMove = $games->listWaitingFor($userId, self::GAMES_PER_PAGE, ($page - 1) * self::GAMES_PER_PAGE);
        $theirMove = $games->listOpponentToMove($userId, self::OTHER_GAMES);
        $finished = $games->listForPlayer($userId, Game::STATE_FINISHED, self::LIST_LENGTH);

        $forMe = array_values(array_filter(
            $challenges->waitingFor($userId, $now),
            fn (Challenge $challenge): bool => $challenge->opponentId === $userId,
        ));
        $open = array_values(array_filter(
            $challenges->waitingOpen($now, 50),
            fn (Challenge $challenge): bool => $challenge->challengerId === $userId || $rules->mayContact($userId, $challenge->challengerId),
        ));
        $open = array_slice($open, 0, self::LIST_LENGTH);

        $live = [];
        if ($this->services->switches()->isOn(FeatureSwitches::PLAYER_LIST_VISIBILITY)) {
            foreach ($games->listAll(Game::STATE_RUNNING, 30) as $game) {
                if (!$game->isPlayer($userId) && count($live) < self::LIST_LENGTH) {
                    $live[] = $game;
                }
            }
        }

        $userIds = [];
        foreach ([...$myMove, ...$theirMove, ...$finished, ...$live] as $game) {
            $userIds[] = $game->whiteId;
            $userIds[] = $game->blackId;
        }
        foreach ([...$forMe, ...$open] as $challenge) {
            $userIds[] = $challenge->challengerId;
        }

        return [
            'waitingGames' => $myMove,
            'theirMoveGames' => $theirMove,
            'finishedGames' => $finished,
            'challengesForMe' => $forMe,
            'openChallenges' => $open,
            'liveGames' => $live,
            'usernames' => $this->services->users()->usernamesOf($userIds),
            'page' => $page,
            'pageCount' => $pageCount,
        ];
    }
}
