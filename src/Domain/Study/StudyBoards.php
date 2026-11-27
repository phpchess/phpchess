<?php

declare(strict_types=1);

namespace PhpChess\Domain\Study;

use PhpChess\Domain\Identity\User;
use PhpChess\Infra\System\SettingsRepository;

/**
 * The rules around study boards (C6-1, A132, A133): who may make one,
 * who may see one, and the kinds and visibilities a board can have.
 *
 * Kinds (A132): a plain study now; the later chunks add their own:
 *   study  = a member's own analysis (C6-1)
 *   review = the commentary on a finished game (C6-2)
 *   ladder = a ladder lesson (C6-3)
 *   lesson = a lesson game, annotated by both sides (C6-4)
 *
 * Visibility: private (the owner only), class (the owner's class, C6-4),
 * public (every member, only after an admin approved it, A133).
 */
final class StudyBoards
{
    public const array KINDS = ['study', 'review', 'ladder', 'lesson'];

    public const string PRIVATE = 'private';
    public const string CLASS_ONLY = 'class';
    public const string PUBLIC = 'public';
    public const array VISIBILITIES = [self::PRIVATE, self::CLASS_ONLY, self::PUBLIC];

    /** The admin setting: who may make boards (A133). */
    public const string CREATORS_SETTING = 'study.board_creators';
    public const string CREATORS_ALL = 'all';
    public const string CREATORS_TEACHERS = 'teachers';
    public const string CREATORS_ADMINS = 'admins';
    public const array CREATORS = [self::CREATORS_ALL, self::CREATORS_TEACHERS, self::CREATORS_ADMINS];

    /** The most boards one member may keep. */
    public const int MAX_BOARDS_PER_MEMBER = 200;

    /** Who may make boards, as the admin set it (everyone, when not set). */
    public static function creatorsIn(SettingsRepository $settings): string
    {
        $value = $settings->getString(self::CREATORS_SETTING, self::CREATORS_ALL);

        return in_array($value, self::CREATORS, true) ? $value : self::CREATORS_ALL;
    }

    /** True when this member may make and edit their own boards. Admins always may. */
    public static function mayCreate(User $user, string $creators): bool
    {
        return match ($creators) {
            self::CREATORS_ALL => true,
            self::CREATORS_TEACHERS => $user->isAdmin() || $user->role === User::ROLE_TEACHER,
            default => $user->isAdmin(),
        };
    }

    /**
     * True when this member may look at a board.
     *
     * @param array{owner_id: int, visibility: string, is_approved: bool} $board
     */
    public static function maySee(User $user, array $board): bool
    {
        if ($board['owner_id'] === $user->id || $user->isAdmin()) {
            return true;
        }

        return $board['visibility'] === self::PUBLIC && $board['is_approved'];
    }
}
