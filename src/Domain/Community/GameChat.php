<?php

declare(strict_types=1);

namespace PhpChess\Domain\Community;

use PhpChess\Domain\ActionRefused;
use PhpChess\Domain\Play\Game;
use PhpChess\Infra\Community\GameChatRepository;
use PhpChess\Infra\System\SettingsRepository;

/**
 * Game chat (G-12): off, fixed phrases only, or free text.
 *
 * The "game chat" switch turns it on or off for the whole site (K8). The
 * admin chooses the kind on the community settings page (setting
 * "game_chat.mode"): "phrases" (the default, good for schools) offers only
 * the fixed phrases from the language file, "free" allows any text, which
 * the word filter cleans (C-05). Only the two players write, and only while
 * the game runs (A61); anyone who may watch the game reads. Games against
 * the computer have no chat at all (GameController, A61).
 */
final class GameChat
{
    public const string MODE_PHRASES = 'phrases';
    public const string MODE_FREE = 'free';
    public const array MODES = [self::MODE_PHRASES, self::MODE_FREE];

    /** The fixed phrases, as language keys "chat.phrase.<name>". */
    public const array PHRASES = ['hello', 'good_luck', 'thanks', 'well_played', 'good_game', 'oops', 'think', 'bye'];

    public const int MAX_LENGTH = 300;

    public function __construct(
        private readonly GameChatRepository $chat,
        private readonly SettingsRepository $settings,
        private readonly WordFilter $wordFilter,
    ) {
    }

    public function mode(): string
    {
        $mode = $this->settings->getString('game_chat.mode', self::MODE_PHRASES);

        return in_array($mode, self::MODES, true) ? $mode : self::MODE_PHRASES;
    }

    /**
     * Adds a line. In phrase mode $text must be a phrase name, stored as
     * "phrase:<name>" so every reader sees it in their own language.
     */
    public function say(Game $game, int $userId, string $text, int $now): void
    {
        if (!$game->isPlayer($userId)) {
            throw new ActionRefused('chat.players_only');
        }
        // A finished game's chat is closed: it can be read, not added to (B17, A61).
        if (!$game->isRunning()) {
            throw new ActionRefused('chat.game_over');
        }
        if ($this->mode() === self::MODE_PHRASES) {
            if (!in_array($text, self::PHRASES, true)) {
                throw new ActionRefused('chat.phrases_only');
            }
            $this->chat->add($game->id, $userId, 'phrase:' . $text, $now);

            return;
        }

        $text = trim($text);
        if ($text === '' || mb_strlen($text) > self::MAX_LENGTH) {
            throw new ActionRefused('chat.bad_length', ['max' => self::MAX_LENGTH]);
        }
        $this->chat->add($game->id, $userId, $this->wordFilter->forPost($text), $now);
    }

    /**
     * @return list<array{id: int, user_id: int, username: string, body: string, created_at: string}>
     */
    public function lines(int $gameId): array
    {
        return $this->chat->forGame($gameId);
    }
}
