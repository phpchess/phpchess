<?php

declare(strict_types=1);

namespace PhpChess\Http\Web;

use PhpChess\Domain\ActionRefused;
use PhpChess\Domain\Identity\User;
use PhpChess\Domain\Lichess\ContentScanner;
use PhpChess\Domain\Lichess\PgnReader;
use PhpChess\Domain\Lichess\UnreadableGame;
use PhpChess\Domain\Play\Game;
use PhpChess\Domain\Study\Drawings;
use PhpChess\Domain\Study\MoveTree;
use PhpChess\Domain\Study\StudyBoards;
use PhpChess\Domain\Study\StudyPgn;
use PhpChess\Domain\Study\StudyScanner;
use PhpChess\Domain\Study\Symbols;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Kernel\Services;

/**
 * The study board (C6-1, A121, A129-A136): moves with side lines,
 * symbols, comments and drawings, saved in the member's own library.
 *
 * Addresses (Routes.php), members only:
 *   GET  /learn/boards                  my boards, public boards, new board, PGN import
 *   POST /learn/boards/new              a new board: empty, or from an own finished game
 *                                       (from=game) or a study game (from=study), with id=
 *   POST /learn/boards/import           boards from pasted or uploaded PGN (StudyScanner, A131)
 *   GET  /learn/boards/{id}             one board: view, and edit for its owner
 *   POST /learn/boards/{id}/save        the whole tree from study-board.js (JSON answer)
 *   GET  /learn/boards/{id}/pgn         the board as a PGN file
 *   POST /learn/boards/{id}/rename      a new title
 *   POST /learn/boards/{id}/visibility  private or public (public waits for an admin, A133)
 *   POST /learn/boards/{id}/approve     an admin approves a public board
 *   POST /learn/boards/{id}/remove      the owner (or an admin) removes a board
 *
 * Who may make boards is the admin's setting (A133, StudyBoards).
 * Every move the browser sends is checked again here (MoveTree::fromList).
 */
final class StudyBoardController
{
    public function __construct(
        private readonly Services $services,
        private readonly MemberPages $pages,
    ) {
    }

    // ===== The list =====

    public function index(Request $request): Response
    {
        $user = $this->pages->user();
        $boards = $this->services->studyBoards();

        return $this->pages->render($request, 'study/index', 'study_board.title', [
            'ownBoards' => $boards->ownBoards($user->id),
            'publicBoards' => $boards->publicBoards(),
            'waitingBoards' => $user->isAdmin() ? $boards->publicBoards(true) : [],
            'mayCreate' => $this->mayCreate($user),
            'maxBytes' => ContentScanner::MAX_BYTES,
        ]);
    }

    // ===== Making boards =====

    public function create(Request $request): Response
    {
        $user = $this->pages->user();
        try {
            $this->checkMayCreateMore($user);
            $from = $request->formValue('from');
            $sourceId = (int) $request->formValue('id', '0');
            [$title, $tree, $result, $tags] = match ($from) {
                'game' => $this->fromOwnGame($user, $sourceId),
                'study' => $this->fromStudyGame($user, $sourceId),
                default => [$this->pages->text('study_board.untitled'), new MoveTree(), '*', []],
            };
            $boardId = $this->services->studyBoards()->create(
                $user->id, $title, 'study', in_array($from, ['game', 'study'], true) ? $from : '', $from === '' ? 0 : $sourceId, $tree, $result, $tags,
            );
        } catch (ActionRefused $refusal) {
            return $this->pages->refused($request, '/learn/boards', $refusal);
        }

        return Response::redirect($request->basePath . '/learn/boards/' . $boardId);
    }

    /** Boards from PGN text: pasted, or a file (A131). One board per game that passes. */
    public function import(Request $request): Response
    {
        $user = $this->pages->user();
        $pgnText = $request->formValue('pgn');
        $file = $request->file('pgn_file');
        if ($file !== null && $file->isUsable()) {
            if ($file->size > ContentScanner::MAX_BYTES) {
                return $this->pages->refused($request, '/learn/boards', new ActionRefused('study_board.too_large'));
            }
            $pgnText = $file->contents();
        }
        if (trim($pgnText) === '') {
            return $this->pages->refused($request, '/learn/boards', new ActionRefused('study_board.nothing_to_import'));
        }

        try {
            $this->checkMayCreateMore($user);
        } catch (ActionRefused $refusal) {
            return $this->pages->refused($request, '/learn/boards', $refusal);
        }
        $scan = (new StudyScanner($this->services->wordFilter()))->scan($pgnText);
        $room = StudyBoards::MAX_BOARDS_PER_MEMBER - $this->services->studyBoards()->countOwn($user->id);
        $lastId = 0;
        $added = 0;
        foreach (array_slice($scan['accepted'], 0, max(0, $room)) as $game) {
            $lastId = $this->services->studyBoards()->create(
                $user->id, self::titleFrom($game['tags'], $this->pages->text('study_board.untitled')), 'study', 'pgn', 0, $game['tree'], $game['result'], $game['tags'],
            );
            $added++;
        }
        // Every rejected game goes to the audit log, for the admin's eyes, as with every scanner (A81).
        foreach (array_slice($scan['rejected'], 0, 50) as $reason) {
            $this->services->auditLog()->record($user->id, 'study.rejected', 'study board import: ' . $reason, $request->clientIp);
        }
        if ($added === 0) {
            return $this->pages->refused($request, '/learn/boards', new ActionRefused('study_board.rejected'));
        }
        if ($added === 1 && $scan['rejected'] === []) {
            return Response::redirect($request->basePath . '/learn/boards/' . $lastId);
        }

        return $this->pages->done($request, '/learn/boards', 'study_board.imported', ['added' => $added, 'rejected' => count($scan['rejected'])]);
    }

    // ===== One board =====

    /** @param array<string, string> $parts */
    public function show(Request $request, array $parts): Response
    {
        $user = $this->pages->user();
        $board = $this->services->studyBoards()->find(MemberPages::idFrom($parts));
        if ($board === null || !StudyBoards::maySee($user, $board)) {
            return $this->pages->notFound($request);
        }
        $isOwner = $board['owner_id'] === $user->id;

        return $this->services->pages()->render($request, 'study/board', $board['title'], [
            'board' => $board,
            'tree' => $this->services->studyBoards()->tree($board['id'])->toList(),
            'mayEdit' => $isOwner && $this->mayCreate($user),
            'isOwner' => $isOwner,
            'isAdmin' => $user->isAdmin(),
            'moveSymbols' => Symbols::MOVE,
            'positionSymbols' => array_intersect_key(Symbols::POSITION, array_flip(Symbols::POSITION_BUTTONS)),
            'allSymbols' => Symbols::MOVE + Symbols::OTHER + Symbols::POSITION,
            'colours' => Drawings::COLOURS,
            'maxComment' => MoveTree::MAX_COMMENT_LENGTH,
            'boardTheme' => $this->services->preferenceRepository()->findForUser($user->id)->boardTheme,
        ]);
    }

    /**
     * Saves the whole tree the browser sends in "tree" (JSON). The answer
     * is {ok: true, tree} with the tree as saved, or {error: text}.
     *
     * @param array<string, string> $parts
     */
    public function save(Request $request, array $parts): Response
    {
        $user = $this->pages->user();
        $board = $this->services->studyBoards()->find(MemberPages::idFrom($parts));
        if ($board === null || $board['owner_id'] !== $user->id || !$this->mayCreate($user)) {
            return Response::json(['error' => $this->pages->text('page.not_found')], 404);
        }
        $entries = json_decode($request->formValue('tree'), true);
        if (!is_array($entries) || !array_is_list($entries)) {
            return Response::json(['error' => $this->pages->text('study.bad_tree')], 400);
        }
        try {
            $authors = $this->services->studyBoards()->authors($board['id']);
            $tree = MoveTree::fromList($board['start_fen'], $entries, $user->id, $authors);
            if (!(new StudyScanner($this->services->wordFilter()))->checkTree($tree)) {
                throw new ActionRefused('study_board.blocked_word');
            }
        } catch (ActionRefused $refusal) {
            return Response::json(['error' => $this->pages->text($refusal->languageKey)], 422);
        }
        $this->services->studyBoards()->saveTree($board['id'], $tree);

        return Response::json(['ok' => true, 'tree' => $tree->toList()]);
    }

    /** @param array<string, string> $parts */
    public function pgn(Request $request, array $parts): Response
    {
        $user = $this->pages->user();
        $board = $this->services->studyBoards()->find(MemberPages::idFrom($parts));
        if ($board === null || !StudyBoards::maySee($user, $board)) {
            return $this->pages->notFound($request);
        }
        $tags = ['Event' => $board['title'], 'Site' => '?', 'Date' => '????.??.??', 'Round' => '-', 'White' => '?', 'Black' => '?'];
        foreach ($board['tags'] as $name => $value) {
            $tags[$name] = $value;
        }
        $pgn = StudyPgn::write($tags, $this->services->studyBoards()->tree($board['id']), $board['result']);

        return new Response(200, $pgn, [
            'Content-Type' => 'application/x-chess-pgn; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="study-' . $board['id'] . '.pgn"',
        ]);
    }

    /** @param array<string, string> $parts */
    public function rename(Request $request, array $parts): Response
    {
        $board = $this->ownBoard($parts);
        $title = trim($request->formValue('title'));
        if ($board === null) {
            return $this->pages->notFound($request);
        }
        if ($title === '' || StudyScanner::cleanText($title) !== $title || $this->services->wordFilter()->blocks($title)) {
            return $this->pages->refused($request, '/learn/boards/' . $board['id'], new ActionRefused('study_board.bad_title'));
        }
        $this->services->studyBoards()->rename($board['id'], $title);

        return $this->pages->done($request, '/learn/boards/' . $board['id'], 'study_board.saved');
    }

    /** @param array<string, string> $parts */
    public function visibility(Request $request, array $parts): Response
    {
        $board = $this->ownBoard($parts);
        $visibility = $request->formValue('visibility');
        // "class" is for lesson boards (C6-4); members choose private or public here.
        if ($board === null || !in_array($visibility, [StudyBoards::PRIVATE, StudyBoards::PUBLIC], true)) {
            return $this->pages->notFound($request);
        }
        $this->services->studyBoards()->setVisibility($board['id'], $visibility);

        return $this->pages->done($request, '/learn/boards/' . $board['id'], $visibility === StudyBoards::PUBLIC ? 'study_board.waits_for_admin' : 'study_board.saved');
    }

    /** @param array<string, string> $parts */
    public function approve(Request $request, array $parts): Response
    {
        $user = $this->pages->user();
        $board = $this->services->studyBoards()->find(MemberPages::idFrom($parts));
        if ($board === null || !$user->isAdmin() || $board['visibility'] !== StudyBoards::PUBLIC) {
            return $this->pages->notFound($request);
        }
        $this->services->studyBoards()->approve($board['id']);
        $this->services->auditLog()->record($user->id, 'study_board.approve', 'board ' . $board['id'] . ' made public', $request->clientIp);

        return $this->pages->done($request, '/learn/boards', 'study_board.approved');
    }

    /** @param array<string, string> $parts */
    public function remove(Request $request, array $parts): Response
    {
        $user = $this->pages->user();
        $board = $this->services->studyBoards()->find(MemberPages::idFrom($parts));
        if ($board === null || ($board['owner_id'] !== $user->id && !$user->isAdmin())) {
            return $this->pages->notFound($request);
        }
        $this->services->studyBoards()->remove($board['id']);

        return $this->pages->done($request, '/learn/boards', 'study_board.removed');
    }

    // ===== Helpers =====

    private function mayCreate(User $user): bool
    {
        return StudyBoards::mayCreate($user, StudyBoards::creatorsIn($this->services->settings()));
    }

    /** @throws ActionRefused when the member may not make (more) boards */
    private function checkMayCreateMore(User $user): void
    {
        if (!$this->mayCreate($user)) {
            throw new ActionRefused('study_board.not_allowed');
        }
        if ($this->services->studyBoards()->countOwn($user->id) >= StudyBoards::MAX_BOARDS_PER_MEMBER) {
            throw new ActionRefused('study_board.too_many', ['max' => StudyBoards::MAX_BOARDS_PER_MEMBER]);
        }
    }

    /**
     * The logged-in member's own board, when they may still edit.
     *
     * @param array<string, string> $parts
     * @return array{id: int, owner_id: int, owner: string, title: string, kind: string, visibility: string, is_approved: bool, source: string, source_id: int, start_fen: string, result: string, tags: array<string, string>, updated_at: int}|null
     */
    private function ownBoard(array $parts): ?array
    {
        $user = $this->pages->user();
        $board = $this->services->studyBoards()->find(MemberPages::idFrom($parts));

        return $board !== null && $board['owner_id'] === $user->id && $this->mayCreate($user) ? $board : null;
    }

    /**
     * A board from one of the member's own finished games (A126: a review
     * of one's own game, after the game).
     *
     * @return array{0: string, 1: MoveTree, 2: string, 3: array<string, string>}
     */
    private function fromOwnGame(User $user, int $gameId): array
    {
        $games = $this->services->play()->gameRepository();
        $game = $games->find($gameId);
        if ($game === null || $game->state !== Game::STATE_FINISHED || !in_array($user->id, [$game->whiteId, $game->blackId], true)) {
            throw new ActionRefused('study_board.not_allowed');
        }
        $tree = new MoveTree($game->startFen);
        $last = 0;
        foreach ($games->moves($game->id) as $move) {
            $last = $tree->addMove($last, $move['san']);
        }
        $names = $this->services->users()->usernamesOf([$game->whiteId, $game->blackId]);
        $tags = ['White' => $names[$game->whiteId] ?? '?', 'Black' => $names[$game->blackId] ?? '?', 'Date' => gmdate('Y.m.d', $game->startedAt)];

        return [$tags['White'] . ' – ' . $tags['Black'], $tree, $game->pgnResult(), $tags];
    }

    /**
     * A board from a study game the member may see (C5-1), with its comments.
     *
     * @return array{0: string, 1: MoveTree, 2: string, 3: array<string, string>}
     */
    private function fromStudyGame(User $user, int $studyGameId): array
    {
        $game = $this->services->studies()->findGame($studyGameId);
        if ($game === null || ($game['theme_id'] === null && $game['owner_id'] !== $user->id)) {
            throw new ActionRefused('study_board.not_allowed');
        }
        try {
            $read = PgnReader::readGame($game['pgn']);
            $tree = new MoveTree($read->startFen);
            $tree->setComment(0, $read->comments[0] ?? '');
            $last = 0;
            foreach ($read->sanMoves as $index => $san) {
                $last = $tree->addMove($last, $san);
                $tree->setComment($last, $read->comments[$index + 1] ?? '');
            }
        } catch (UnreadableGame) {
            throw new ActionRefused('study_board.rejected');
        }
        $tags = array_intersect_key($read->tags, array_flip(['Event', 'Date', 'White', 'Black', 'ECO', 'Opening']));

        return [$game['white'] . ' – ' . $game['black'], $tree, $read->result, $tags];
    }

    /** @param array<string, string> $tags */
    private static function titleFrom(array $tags, string $fallback): string
    {
        $white = $tags['White'] ?? '';
        $black = $tags['Black'] ?? '';
        if ($white !== '' && $white !== '?' && $black !== '' && $black !== '?') {
            return $white . ' – ' . $black;
        }

        return ($tags['Event'] ?? '') !== '' && ($tags['Event'] ?? '') !== '?' ? $tags['Event'] : $fallback;
    }
}
