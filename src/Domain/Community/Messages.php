<?php

declare(strict_types=1);

namespace PhpChess\Domain\Community;

use PhpChess\Domain\ActionRefused;
use PhpChess\Domain\Notify\Notices;
use PhpChess\Domain\School\ClassRules;
use PhpChess\Infra\Community\MessageRepository;
use PhpChess\Infra\Identity\UserRepository;
use PhpChess\Infra\Notify\NoticeRepository;

/**
 * Direct messages between members (C-04).
 *
 * Sending checks, in order: the recipient exists and is active, it is not
 * yourself, the school's class rule allows it (SCH-05), the text is not
 * empty or too long, and the sender stays under the sending limit. The
 * word filter (C-05) cleans the text. The recipient gets one notice
 * ("new messages"), which goes on by push or email like the others (N-03).
 * The "direct messages" switch is checked by the routes (K8).
 */
final class Messages
{
    public const int MAX_LENGTH = 2000;

    /** At most this many messages per member per hour, against spam. */
    public const int MAX_PER_HOUR = 60;

    public function __construct(
        private readonly MessageRepository $messages,
        private readonly UserRepository $users,
        private readonly NoticeRepository $notices,
        private readonly ClassRules $classRules,
        private readonly WordFilter $wordFilter,
    ) {
    }

    /** Sends a message and returns its id; throws ActionRefused when it may not be sent. */
    public function send(int $fromUserId, int $toUserId, string $text, int $now): int
    {
        $recipient = $this->users->findById($toUserId);
        if ($recipient === null || !$recipient->isActive()) {
            throw new ActionRefused('messages.unknown_player');
        }
        if ($toUserId === $fromUserId) {
            throw new ActionRefused('messages.not_to_yourself');
        }
        if (!$this->classRules->mayContact($fromUserId, $toUserId)) {
            throw new ActionRefused('school.outside_class');
        }
        $text = trim($text);
        if ($text === '') {
            throw new ActionRefused('messages.empty');
        }
        if (mb_strlen($text) > self::MAX_LENGTH) {
            throw new ActionRefused('messages.too_long', ['max' => self::MAX_LENGTH]);
        }
        if ($this->messages->countSentSince($fromUserId, $now - 3600) >= self::MAX_PER_HOUR) {
            throw new ActionRefused('messages.too_many');
        }

        $messageId = $this->messages->add($fromUserId, $toUserId, $this->wordFilter->forPost($text), $now);
        $this->notices->add($toUserId, Notices::MESSAGE_RECEIVED, null, null, $fromUserId, $now);

        return $messageId;
    }

    /**
     * Opens a conversation: its messages, and those sent to the reader are now read.
     *
     * @return list<array{id: int, from_user_id: int, body: string, created_at: string, is_read: bool}>
     */
    public function open(int $userId, int $otherUserId, int $afterId, int $now): array
    {
        $this->messages->markRead($userId, $otherUserId, $now);

        return $this->messages->conversation($userId, $otherUserId, $afterId);
    }
}
