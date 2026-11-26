<?php

declare(strict_types=1);

namespace PhpChess\Http\Web;

use PhpChess\Domain\ActionRefused;
use PhpChess\Domain\Chess\MoveGenerator;
use PhpChess\Domain\Chess\Position;
use PhpChess\Domain\Chess\San;
use PhpChess\Domain\Lichess\PgnReader;
use PhpChess\Domain\Lichess\ReadGame;
use PhpChess\Domain\Lichess\UnreadableGame;
use PhpChess\Domain\Lichess\ContentScanner;
use PhpChess\Domain\Lichess\LichessContent;
use PhpChess\Domain\Lichess\StudyImport;
use PhpChess\Domain\Lichess\StudyTopics;
use PhpChess\Domain\System\FeatureSwitches;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Kernel\Services;

/**
 * The Learn pages (C5-1, L-01, L-03): the way in to puzzles, study games
 * and Lichess TV.
 *
 * Addresses (Routes.php), members only:
 *   GET  /learn                    the hub: puzzles, the study themes, my own imports, TV
 *   GET  /learn/themes/{id}        the games of one study theme
 *   GET  /learn/games/{id}         one study game on a board, move by move
 *   POST /learn/import             a player imports one Lichess game ("external content")
 *   POST /learn/paste              a player pastes one game as PGN (when the admin allows it, B37)
 *   POST /learn/games/{id}/remove  a player removes one of their own imports
 *   POST /learn/games/{id}/keep    a player keeps one of their own games, or stops keeping it (B37)
 *   GET  /learn/tv                 Lichess TV on our own board ("external content")
 *   GET  /learn/tv/now             the TV game now: {channel, gameId, pgn} ("external content")
 *
 * Study themes and their games are local content (an admin's uploads work
 * offline), so they need no switch. Everything that asks lichess.org does.
 * A player's own imports are seen by that player only (A88).
 */
final class LearnController
{
    /** How long the TV game is kept before Lichess is asked again, in seconds. */
    private const int TV_CACHE_SECONDS = 10;

    /** The TV channels players can pick: Lichess's keys in /api/tv/channels (standard chess only). */
    public const array TV_CHANNELS = ['best', 'classical', 'rapid', 'blitz', 'bullet'];

    public function __construct(
        private readonly Services $services,
        private readonly MemberPages $pages,
    ) {
    }

    // --- The hub ---

    public function hub(Request $request): Response
    {
        $user = $this->pages->user();

        return $this->services->pages()->render($request, 'learn/hub', $this->pages->text('learn.title'), [
            'themes' => $this->services->studies()->themes(),
            'sectionOrder' => StudyTopics::SECTIONS,
            'ownGames' => $this->services->studies()->ownGames($user->id),
            'lichessIsOn' => $this->services->switches()->isOn(FeatureSwitches::EXTERNAL_CONTENT),
            'importsAreOn' => $this->lichessContent()->isShown(LichessContent::IMPORTS),
            'pasteIsOpen' => $this->mayPaste($user->isAdmin()),
            'maxOwnGames' => StudyImport::maxOwnGamesIn($this->services->settings()),
            'maxBytes' => ContentScanner::MAX_BYTES,
            'ownGameDays' => StudyImport::ownGameDaysIn($this->services->settings()),
        ]);
    }

    // --- Study games ---

    /** @param array<string, string> $parts */
    public function theme(Request $request, array $parts): Response
    {
        $this->pages->user();
        $theme = $this->services->studies()->findTheme(MemberPages::idFrom($parts));
        if ($theme === null) {
            return $this->pages->notFound($request);
        }

        return $this->services->pages()->render($request, 'learn/theme', $theme['title'], [
            'theme' => $theme,
            'games' => $this->services->studies()->gamesOfTheme($theme['id']),
        ]);
    }

    /** @param array<string, string> $parts */
    public function game(Request $request, array $parts): Response
    {
        $user = $this->pages->user();
        $game = $this->services->studies()->findGame(MemberPages::idFrom($parts));

        // A player's own import is seen by that player only (A88).
        if ($game === null || ($game['theme_id'] === null && $game['owner_id'] !== $user->id)) {
            return $this->pages->notFound($request);
        }

        // The positions after every move, for the board's buttons. The stored
        // PGN passed the scanner, so it reads; if it ever doesn't, show nothing.
        try {
            $readGame = PgnReader::readGame($game['pgn']);
        } catch (UnreadableGame) {
            return $this->pages->notFound($request);
        }

        // A move the board can't follow is reported, not skipped quietly (B39).
        $positions = self::positionsOf($readGame);

        return $this->services->pages()->render($request, 'learn/game', $game['white'] . ' – ' . $game['black'], [
            'game' => $game,
            'positions' => $positions,
            'isComplete' => count($positions) === count($readGame->sanMoves) + 1,
            'lichessLink' => self::lichessLinkOf($game['source'], $game['source_id']),
            'theme' => $game['theme_id'] === null ? null : $this->services->studies()->findTheme($game['theme_id']),
            'isOwn' => $game['owner_id'] === $user->id,
            'ownGameDays' => StudyImport::ownGameDaysIn($this->services->settings()),
            'boardTheme' => $this->services->preferenceRepository()->findForUser($user->id)->boardTheme,
        ]);
    }

    public function import(Request $request): Response
    {
        $user = $this->pages->user();
        if (!$this->lichessContent()->isShown(LichessContent::IMPORTS)) {
            return $this->pages->notFound($request);
        }
        $link = $request->formValue('link');

        return $this->importOwnGame($request, $user->id, fn (): int => $this->services->studyImport()->importOneGame($link, null, $user->id));
    }

    /** A player pastes one game as PGN text (B37); a 404 while the admin hasn't allowed it. */
    public function paste(Request $request): Response
    {
        $user = $this->pages->user();
        if (!$this->mayPaste($user->isAdmin())) {
            return $this->pages->notFound($request);
        }
        $pgnText = $request->formValue('pgn');
        if (strlen($pgnText) > ContentScanner::MAX_BYTES) {
            return $this->services->pages()->redirectWithMessage($request, '/learn', 'error', $this->pages->text('study.rejected'));
        }

        return $this->importOwnGame($request, $user->id, fn (): int => $this->services->studyImport()->importPastedGame($pgnText, $user->id));
    }

    /** @param array<string, string> $parts */
    public function removeOwnGame(Request $request, array $parts): Response
    {
        $user = $this->pages->user();
        $game = $this->services->studies()->findGame(MemberPages::idFrom($parts));
        if ($game === null || $game['owner_id'] !== $user->id) {
            return $this->pages->notFound($request);
        }
        $this->services->studies()->removeGame($game['id']);

        return $this->services->pages()->redirectWithMessage($request, '/learn', 'success', $this->pages->text('study.removed'));
    }

    /** @param array<string, string> $parts */
    public function keepOwnGame(Request $request, array $parts): Response
    {
        $user = $this->pages->user();
        $game = $this->services->studies()->findGame(MemberPages::idFrom($parts));
        if ($game === null || $game['owner_id'] !== $user->id) {
            return $this->pages->notFound($request);
        }
        $this->services->studies()->setKept($game['id'], !$game['is_kept']);

        return $this->services->pages()->redirectWithMessage($request, '/learn/games/' . $game['id'], 'success', $this->pages->text($game['is_kept'] ? 'study.not_kept' : 'study.kept'));
    }

    // --- Lichess TV ---

    public function tv(Request $request): Response
    {
        $user = $this->pages->user();

        $chosen = $request->queryValue('channel', 'best');

        return $this->services->pages()->render($request, 'learn/tv', $this->pages->text('tv.title'), [
            'channels' => self::TV_CHANNELS,
            'chosenChannel' => in_array($chosen, self::TV_CHANNELS, true) ? $chosen : 'best',
            'boardTheme' => $this->services->preferenceRepository()->findForUser($user->id)->boardTheme,
        ]);
    }

    /**
     * The game now on one TV channel: {channel, gameId, white, black, fen}.
     * Many viewers share one answer: Lichess is asked at most once every
     * TV_CACHE_SECONDS. The game's PGN passes the content scanner like
     * every other game (A81); a game it rejects is not shown.
     */
    public function tvNow(Request $request): Response
    {
        $this->pages->user();
        $channel = $request->queryValue('channel', 'best');
        if (!in_array($channel, self::TV_CHANNELS, true)) {
            $channel = 'best';
        }

        // --- The shared answer, while it is fresh ---
        $settings = $this->services->settings();
        $cacheName = 'lichess.tv.' . md5($channel);
        $cached = json_decode($settings->getString($cacheName), true);
        if (is_array($cached) && is_int($cached['at'] ?? null) && $cached['at'] > $this->services->now - self::TV_CACHE_SECONDS) {
            unset($cached['at']);

            return Response::json($cached);
        }

        // --- Ask Lichess: which game is on, and its moves so far ---
        try {
            $lichess = $this->services->lichess();
            $channels = json_decode($lichess->tvChannels(), true);
            $onChannel = is_array($channels) ? ($channels[$channel] ?? null) : null;
            $gameId = is_array($onChannel) && is_string($onChannel['gameId'] ?? null) ? $onChannel['gameId'] : '';
            if (StudyImport::gameIdFrom($gameId) === null) {
                throw new ActionRefused('lichess.unavailable');
            }
            $scan = (new ContentScanner($this->services->wordFilter()))->scan($lichess->gamePgn($gameId));
        } catch (ActionRefused $refusal) {
            return Response::json(['error' => $this->pages->text($refusal->languageKey)], 503);
        }
        if (count($scan->accepted) !== 1) {
            return Response::json(['error' => $this->pages->text('lichess.unavailable')], 503);
        }

        $game = $scan->accepted[0];
        $positions = self::positionsOf($game);
        $answer = [
            'channel' => $channel,
            'gameId' => $gameId,
            'white' => $game->tag('White') . ($game->tag('WhiteElo') !== '' ? ' (' . $game->tag('WhiteElo') . ')' : ''),
            'black' => $game->tag('Black') . ($game->tag('BlackElo') !== '' ? ' (' . $game->tag('BlackElo') . ')' : ''),
            'fen' => $positions[count($positions) - 1]['fen'],
            'lastMove' => $positions[count($positions) - 1]['san'],
        ];
        $settings->set($cacheName, (string) json_encode($answer + ['at' => $this->services->now]));

        return Response::json($answer);
    }

    // --- Helpers ---

    /** The game's page on lichess.org: a game, or a study (A95); '' for uploads and pastes. */
    private static function lichessLinkOf(string $source, string $sourceId): string
    {
        if ($sourceId === '') {
            return '';
        }

        return $source === 'study' ? 'https://lichess.org/study/' . $sourceId : 'https://lichess.org/' . $sourceId;
    }

    /** Which kinds of Lichess content the admin turned on (A96). */
    private function lichessContent(): LichessContent
    {
        return new LichessContent($this->services->switches(), $this->services->settings());
    }

    /** Admins may always paste; players only while the admin allows it (B37). */
    private function mayPaste(bool $isAdmin): bool
    {
        return $isAdmin || StudyImport::pasteIsOpenIn($this->services->settings());
    }

    /**
     * Runs one import into a player's own list, within the admin's hourly
     * limit, and shows the game or says why not.
     *
     * @param \Closure(): int $import  gives the stored game's id
     */
    private function importOwnGame(Request $request, int $userId, \Closure $import): Response
    {
        $perHour = StudyImport::importsPerHourIn($this->services->settings());
        if (!$this->services->rateLimiter()->hit('study-import:' . $userId, $perHour, 3600, $this->services->now)) {
            return $this->services->pages()->redirectWithMessage($request, '/learn', 'error', $this->pages->text('lichess.busy'));
        }
        try {
            $gameId = $import();
        } catch (ActionRefused $refusal) {
            return $this->services->pages()->redirectWithMessage($request, '/learn', 'error', $this->pages->text($refusal->languageKey));
        }

        return $this->services->pages()->redirectWithMessage($request, '/learn/games/' . $gameId, 'success', $this->pages->text('study.imported'));
    }

    /**
     * The start position and the position after each move, with the move
     * that led there and its comment (B38), for the game viewer
     * (learn/game, study-game.js). It stops at a move it can't play; the
     * caller compares the count with the game's moves (B39).
     *
     * @return list<array{fen: string, san: string, comment: string}>
     */
    public static function positionsOf(ReadGame $game): array
    {
        $position = Position::fromFen($game->startFen);
        $positions = [['fen' => $position->toFen(), 'san' => '', 'comment' => $game->comments[0] ?? '']];
        foreach ($game->sanMoves as $index => $san) {
            $played = false;
            foreach (MoveGenerator::legalMoves($position) as $move) {
                if (San::of($position, $move) === $san) {
                    $position = $position->play($move);
                    $positions[] = ['fen' => $position->toFen(), 'san' => $san, 'comment' => $game->comments[$index + 1] ?? ''];
                    $played = true;
                    break;
                }
            }
            if (!$played) {
                break;
            }
        }

        return $positions;
    }
}
