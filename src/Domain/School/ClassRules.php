<?php

declare(strict_types=1);

namespace PhpChess\Domain\School;

use PhpChess\Domain\Identity\User;
use PhpChess\Domain\System\FeatureSwitches;
use PhpChess\Infra\Identity\UserRepository;
use PhpChess\Infra\School\SchoolRepository;

/**
 * Who may reach whom in school mode (SCH-05, SCH-06).
 *
 * The rule, in one place for games, challenges, messages and buddies:
 * - school mode off: everyone may reach everyone;
 * - a student in a class "limited to the class" may reach only members of
 *   their own classes, their teachers and admins, and only those may
 *   reach them;
 * - everyone else is free.
 * Games::start, Messages::send and the buddy list all ask mayContact().
 * Games::start also asks isTournamentGuest(): guest logins (A109) only play
 * in their own tournament.
 */
final class ClassRules
{
    public function __construct(
        private readonly SchoolRepository $school,
        private readonly UserRepository $users,
        private readonly FeatureSwitches $switches,
    ) {
    }

    /** True when the two users may play, write to and list each other. */
    public function mayContact(int $userId, int $otherUserId): bool
    {
        if (!$this->switches->isOn(FeatureSwitches::SCHOOL_MODE)) {
            return true;
        }

        return $this->mayReach($userId, $otherUserId) && $this->mayReach($otherUserId, $userId);
    }

    /**
     * True for a guest login made for one tournament (A109): it plays only
     * that tournament's games, so Games::start() refuses it.
     */
    public function isTournamentGuest(int $userId): bool
    {
        return $this->users->guestTournamentOf($userId) !== null;
    }

    /** True when $teacher may see the games and accounts of $studentId (SCH-06): their teacher, or an admin. */
    public function mayLookAfter(User $teacher, int $studentId): bool
    {
        if ($teacher->isAdmin()) {
            return true;
        }

        return $teacher->role === User::ROLE_TEACHER && $this->school->teaches($teacher->id, $studentId);
    }

    /** One direction of the rule: is $userId limited, and if so, is the other one inside the limit? */
    private function mayReach(int $userId, int $otherUserId): bool
    {
        $user = $this->users->findById($userId);
        if ($user === null || $user->role !== User::ROLE_STUDENT || !$this->school->isInLimitedClass($userId)) {
            return true;
        }
        $other = $this->users->findById($otherUserId);
        if ($other !== null && $other->isAdmin()) {
            return true;
        }

        return $this->school->belongTogether($userId, $otherUserId);
    }
}
