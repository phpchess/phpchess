<?php

declare(strict_types=1);

namespace PhpChess\Http\Web;

use PhpChess\Domain\ActionRefused;
use PhpChess\Domain\Lichess\ContentScanner;
use PhpChess\Domain\Lichess\LichessContent;
use PhpChess\Domain\Lichess\PgnReader;
use PhpChess\Domain\Lichess\ReadGame;
use PhpChess\Domain\Lichess\UnreadableGame;
use PhpChess\Domain\Puzzles\PuzzleSet;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Infra\Lichess\LichessCache;
use PhpChess\Kernel\Services;

/**
 * The players' Lichess page (A96): live games, broadcasts, the daily puzzle.
 *
 * Addresses (Routes.php), members only, all behind "external content" (K8):
 *   GET /learn/lichess                         the page: live games, broadcasts, the daily puzzle
 *   GET /learn/lichess/broadcast/{round}       the games of one broadcast round
 *   GET /learn/lichess/broadcast/{round}/{n}   one of those games on our board
 *   GET /learn/lichess/daily                   the daily puzzle in our trainer
 *
 * Each part shows only while the admin has it on (LichessContent); a part
 * that is off answers 404. Only our server asks lichess.org (LichessClient),
 * and answers are shared for a while (LichessCache): live games 10 seconds,
 * broadcasts 5 minutes, the daily puzzle 1 hour. Every game passes the
 * content scanner (A81); names Lichess sends are checked by the word filter
 * and always printed escaped.
 */
final class LichessPagesController
{
    private const int LIVE_SECONDS = 10;
    private const int BROADCAST_SECONDS = 300;
    private const int DAILY_SECONDS = 3600;

    /** The most broadcasts listed, now and soon each. */
    private const int MAX_BROADCASTS = 12;

    public function __construct(
        private readonly Services $services,
        private readonly MemberPages $pages,
    ) {
    }

    // --- The page ---

    public function show(Request $request): Response
    {
        $this->pages->user();
        $content = $this->content();

        $live = [];
        $broadcasts = ['active' => [], 'upcoming' => []];
        $problems = [];
        if ($content->isShown(LichessContent::LIVE)) {
            try {
                $live = $this->liveGames();
            } catch (ActionRefused $refusal) {
                $problems[] = $this->pages->text($refusal->languageKey);
            }
        }
        if ($content->isShown(LichessContent::BROADCASTS)) {
            try {
                $broadcasts = $this->broadcasts();
            } catch (ActionRefused $refusal) {
                $problems[] = $this->pages->text($refusal->languageKey);
            }
        }

        return $this->services->pages()->render($request, 'learn/lichess', $this->pages->text('lichess.page_title'), [
            'showLive' => $content->isShown(LichessContent::LIVE),
            'showBroadcasts' => $content->isShown(LichessContent::BROADCASTS),
            'showDaily' => $content->isShown(LichessContent::DAILY),
            'live' => $live,
            'broadcasts' => $broadcasts,
            'problems' => array_values(array_unique($problems)),
        ]);
    }

    // --- Broadcasts ---

    /** @param array<string, string> $parts */
    public function broadcastRound(Request $request, array $parts): Response
    {
        $this->pages->user();
        $roundId = $parts['round'] ?? '';
        if (!$this->content()->isShown(LichessContent::BROADCASTS) || preg_match('/^[A-Za-z0-9]{8}$/', $roundId) !== 1) {
            return $this->pages->notFound($request);
        }
        try {
            $games = $this->roundGames($roundId);
        } catch (ActionRefused $refusal) {
            return $this->services->pages()->redirectWithMessage($request, '/learn/lichess', 'error', $this->pages->text($refusal->languageKey));
        }

        return $this->services->pages()->render($request, 'learn/broadcast', $this->pages->text('lichess.broadcast'), [
            'roundId' => $roundId,
            'games' => array_map(static fn (ReadGame $game): array => [
                'white' => $game->tag('White'),
                'black' => $game->tag('Black'),
                'result' => $game->result,
                'event' => $game->tag('Event'),
            ], $games),
        ]);
    }

    /** @param array<string, string> $parts */
    public function broadcastGame(Request $request, array $parts): Response
    {
        $user = $this->pages->user();
        $roundId = $parts['round'] ?? '';
        $number = (int) ($parts['n'] ?? 0);
        if (!$this->content()->isShown(LichessContent::BROADCASTS) || preg_match('/^[A-Za-z0-9]{8}$/', $roundId) !== 1) {
            return $this->pages->notFound($request);
        }
        try {
            $games = $this->roundGames($roundId);
        } catch (ActionRefused $refusal) {
            return $this->services->pages()->redirectWithMessage($request, '/learn/lichess', 'error', $this->pages->text($refusal->languageKey));
        }
        if (!isset($games[$number - 1])) {
            return $this->pages->notFound($request);
        }
        $game = $games[$number - 1];
        $positions = LearnController::positionsOf($game);

        // The study game page shows it; this game is not stored, so it has no Keep or Remove.
        return $this->services->pages()->render($request, 'learn/game', $game->tag('White') . ' – ' . $game->tag('Black'), [
            'game' => [
                'id' => 0,
                'white' => $game->tag('White'),
                'black' => $game->tag('Black'),
                'result' => $game->result,
                'opening' => $game->tag('Opening'),
                'event' => $game->tag('Event'),
                'played_on' => $game->tag('Date'),
                'pgn' => $game->toPgn(),
                'is_kept' => false,
                'created_at' => 0,
            ],
            'positions' => $positions,
            'isComplete' => count($positions) === count($game->sanMoves) + 1,
            'isOwn' => false,
            'ownGameDays' => 0,
            'lichessLink' => '',
            'theme' => null,
            'backPath' => '/learn/lichess/broadcast/' . $roundId,
            'boardTheme' => $this->services->preferenceRepository()->findForUser($user->id)->boardTheme,
        ]);
    }

    // --- The daily puzzle ---

    public function daily(Request $request): Response
    {
        $user = $this->pages->user();
        if (!$this->content()->isShown(LichessContent::DAILY)) {
            return $this->pages->notFound($request);
        }
        try {
            $puzzle = $this->dailyPuzzle();
        } catch (ActionRefused $refusal) {
            return $this->services->pages()->redirectWithMessage($request, '/learn/lichess', 'error', $this->pages->text($refusal->languageKey));
        }

        return $this->services->pages()->render($request, 'puzzles/trainer', $this->pages->text('lichess.daily'), [
            'motifs' => PuzzleSet::MOTIFS,
            'levels' => array_keys(PuzzleSet::LEVELS),
            'boardTheme' => $this->services->preferenceRepository()->findForUser($user->id)->boardTheme,
            'puzzleCount' => 1,
            'lichessTrainingLink' => 'https://lichess.org/training/',
            'dailyPuzzle' => $puzzle,
        ]);
    }

    // --- Asking Lichess (through the cache) ---

    private function content(): LichessContent
    {
        return new LichessContent($this->services->switches(), $this->services->settings());
    }

    private function cache(): LichessCache
    {
        return new LichessCache($this->services->appRoot . '/storage/cache/lichess', $this->services->now);
    }

    /**
     * The games on Lichess TV now, one per channel we show.
     *
     * @return list<array{channel: string, player: string, rating: int}>
     */
    private function liveGames(): array
    {
        $json = $this->cache()->remember('tv-channels', self::LIVE_SECONDS, fn (): string => $this->services->lichess()->tvChannels());
        $channels = json_decode($json, true);
        if (!is_array($channels)) {
            throw new ActionRefused('lichess.unavailable');
        }
        $filter = $this->services->wordFilter();
        $live = [];
        foreach (LearnController::TV_CHANNELS as $channel) {
            $entry = $channels[$channel] ?? null;
            if (!is_array($entry)) {
                continue;
            }
            $user = is_array($entry['user'] ?? null) ? $entry['user'] : [];
            $name = is_string($user['name'] ?? null) ? $user['name'] : '';
            $live[] = [
                'channel' => $channel,
                // A Lichess user name is letters, digits, - and _; anything else is not shown.
                'player' => preg_match('/^[A-Za-z0-9_-]{2,30}$/', $name) === 1 && !$filter->blocks($name) ? $name : '',
                'rating' => is_int($entry['rating'] ?? null) ? $entry['rating'] : 0,
            ];
        }

        return $live;
    }

    /**
     * Official broadcasts now and soon: name, round, round id, start.
     *
     * @return array{active: list<array{name: string, round: string, roundId: string, startsAt: int}>,
     *               upcoming: list<array{name: string, round: string, roundId: string, startsAt: int}>}
     */
    private function broadcasts(): array
    {
        $json = $this->cache()->remember('broadcasts-top', self::BROADCAST_SECONDS, fn (): string => $this->services->lichess()->topBroadcasts());
        $answer = json_decode($json, true);
        if (!is_array($answer)) {
            throw new ActionRefused('lichess.unavailable');
        }

        return [
            'active' => $this->broadcastList($answer['active'] ?? null),
            'upcoming' => $this->broadcastList($answer['upcoming'] ?? null),
        ];
    }

    /** @return list<array{name: string, round: string, roundId: string, startsAt: int}> */
    private function broadcastList(mixed $entries): array
    {
        if (!is_array($entries)) {
            return [];
        }
        $filter = $this->services->wordFilter();
        $list = [];
        foreach ($entries as $entry) {
            $tour = is_array($entry) && is_array($entry['tour'] ?? null) ? $entry['tour'] : [];
            $round = is_array($entry) && is_array($entry['round'] ?? null) ? $entry['round'] : [];
            $name = is_string($tour['name'] ?? null) ? mb_substr($tour['name'], 0, 100) : '';
            $roundName = is_string($round['name'] ?? null) ? mb_substr($round['name'], 0, 60) : '';
            $roundId = is_string($round['id'] ?? null) ? $round['id'] : '';
            // Names are free text from outside: a blocked word hides the whole entry.
            if ($name === '' || preg_match('/^[A-Za-z0-9]{8}$/', $roundId) !== 1 || $filter->blocks($name . ' ' . $roundName)) {
                continue;
            }
            $list[] = [
                'name' => $name,
                'round' => $roundName,
                'roundId' => $roundId,
                'startsAt' => is_int($round['startsAt'] ?? null) ? intdiv($round['startsAt'], 1000) : 0,
            ];
            if (count($list) >= self::MAX_BROADCASTS) {
                break;
            }
        }

        return $list;
    }

    /**
     * The games of a broadcast round that passed the scanner.
     *
     * @return list<ReadGame>
     */
    private function roundGames(string $roundId): array
    {
        $pgn = $this->cache()->remember('broadcast-round-' . $roundId, self::BROADCAST_SECONDS, fn (): string => $this->services->lichess()->broadcastRoundPgn($roundId));

        return (new ContentScanner($this->services->wordFilter()))->scan($pgn)->accepted;
    }

    /**
     * Lichess's daily puzzle in our trainer's form: the position before the
     * opponent's move, then that move and the solution (UCI).
     *
     * @return array{id: string, fen: string, moves: list<string>, rating: int}
     */
    private function dailyPuzzle(): array
    {
        $json = $this->cache()->remember('daily-puzzle', self::DAILY_SECONDS, fn (): string => $this->services->lichess()->dailyPuzzle());
        $answer = json_decode($json, true);
        $puzzle = is_array($answer) && is_array($answer['puzzle'] ?? null) ? $answer['puzzle'] : [];
        $game = is_array($answer) && is_array($answer['game'] ?? null) ? $answer['game'] : [];
        $id = is_string($puzzle['id'] ?? null) ? $puzzle['id'] : '';
        $puzzleFen = is_string($puzzle['fen'] ?? null) ? $puzzle['fen'] : '';
        $lastMove = is_string($puzzle['lastMove'] ?? null) ? $puzzle['lastMove'] : '';
        $solution = is_array($puzzle['solution'] ?? null) ? array_values(array_filter($puzzle['solution'], 'is_string')) : [];
        $movesText = is_string($game['pgn'] ?? null) ? $game['pgn'] : '';

        $isUci = static fn (string $move): bool => preg_match('/^[a-h][1-8][a-h][1-8][qrbn]?$/', $move) === 1;
        if (preg_match('/^[A-Za-z0-9]{5}$/', $id) !== 1 || !$isUci($lastMove) || $solution === [] || count(array_filter($solution, $isUci)) !== count($solution)) {
            throw new ActionRefused('lichess.unavailable');
        }

        // Our trainer starts one move earlier, with the opponent's move. Replay
        // the game with our own rules and take the position before the puzzle's.
        try {
            $readGame = PgnReader::readGame("[Result \"*\"]\n\n" . $movesText . " *\n");
        } catch (UnreadableGame) {
            throw new ActionRefused('lichess.unavailable');
        }
        $boardOf = static fn (string $fen): string => explode(' ', $fen)[0];
        $positions = LearnController::positionsOf($readGame);
        foreach ($positions as $index => $position) {
            if ($index > 0 && $boardOf($position['fen']) === $boardOf($puzzleFen)) {
                return [
                    'id' => $id,
                    'fen' => $positions[$index - 1]['fen'],
                    'moves' => array_merge([$lastMove], $solution),
                    'rating' => is_int($puzzle['rating'] ?? null) ? $puzzle['rating'] : 0,
                ];
            }
        }

        throw new ActionRefused('lichess.unavailable');
    }
}
