<?php

declare(strict_types=1);

namespace PhpChess\Domain\School;

use PhpChess\Domain\ActionRefused;
use PhpChess\Domain\Community\WordFilter;
use PhpChess\Domain\Identity\AccountRefused;
use PhpChess\Domain\Identity\PasswordRules;
use PhpChess\Domain\Identity\User;
use PhpChess\Infra\Identity\TokenRepository;
use PhpChess\Infra\Identity\UserRepository;
use PhpChess\Infra\School\SchoolRepository;

/**
 * School mode for teachers (SCH-02 to SCH-04): classes, student accounts
 * made in bulk, login cards and password resets.
 *
 * - A class has a name, a teacher and members. "Limit to the class"
 *   (SCH-05, on by default) keeps its students among themselves.
 * - Students are made in bulk from a list of names, one per line. They
 *   need no email. Each gets a random password nobody knows, and a login
 *   card instead (A41).
 * - A login card is a one-time link, printed as a QR code. Scanning it
 *   logs the student in (and remembers the device). A new card replaces
 *   the old one, so a lost card can be revoked by printing a new one.
 * - A teacher can also set a student's password.
 * A teacher works only with their own classes; admins with all of them.
 */
final class Classes
{
    public const string LOGIN_CARD = 'login_card';
    public const int LOGIN_CARD_SECONDS = 365 * 86400;
    public const int MAX_STUDENTS_AT_ONCE = 60;

    public function __construct(
        private readonly SchoolRepository $school,
        private readonly UserRepository $users,
        private readonly TokenRepository $tokens,
        private readonly WordFilter $wordFilter,
    ) {
    }

    // --- Classes ---

    public function createClass(User $teacher, string $name, int $now): int
    {
        $name = trim($name);
        if ($name === '' || mb_strlen($name) > 80) {
            throw new ActionRefused('school.class_name_rules');
        }

        return $this->school->createClass($name, $teacher->id, true, $now);
    }

    /**
     * The class, when this teacher may manage it; otherwise ActionRefused.
     *
     * @return array{id: int, name: string, teacher_id: int, limit_to_class: bool}
     */
    public function classFor(User $teacher, int $classId): array
    {
        $class = $this->school->findClass($classId);
        if ($class === null || (!$teacher->isAdmin() && $class['teacher_id'] !== $teacher->id)) {
            throw new ActionRefused('school.class_not_found');
        }

        return $class;
    }

    // --- Students ---

    /**
     * Makes student accounts from a list of names, one per line, and adds
     * them to the class. Names already taken get a number added.
     * Returns the new accounts: id => user name.
     *
     * @return array<int, string>
     */
    public function addStudents(User $teacher, int $classId, string $namesText): array
    {
        $this->classFor($teacher, $classId);
        $names = array_values(array_filter(array_map('trim', preg_split('/\R/', $namesText) ?: []), fn (string $name): bool => $name !== ''));
        if ($names === []) {
            throw new ActionRefused('school.no_names');
        }
        if (count($names) > self::MAX_STUDENTS_AT_ONCE) {
            throw new ActionRefused('school.too_many_names', ['max' => self::MAX_STUDENTS_AT_ONCE]);
        }

        $created = [];
        foreach ($names as $name) {
            $username = $this->freeUsername($name);
            $userId = $this->users->create(
                $username,
                null,
                PasswordRules::hash(bin2hex(random_bytes(16))),
                User::ROLE_STUDENT,
                User::STATE_ACTIVE,
            );
            $this->users->markApproved($userId);
            $this->school->addMember($classId, $userId);
            $created[$userId] = $username;
        }

        return $created;
    }

    /**
     * Adds an existing account to the class by user name. A teacher adds
     * students only, and only one in no class yet or already in one of
     * their own classes: otherwise a teacher could take over another
     * teacher's student and set their password (B17 review). An admin adds anyone.
     */
    public function addExistingMember(User $teacher, int $classId, string $username): void
    {
        $this->classFor($teacher, $classId);
        $member = $this->users->findByUsername(trim($username));
        if ($member === null || !$this->isLookedAfterBy($teacher, $member->id)) {
            throw new ActionRefused('school.unknown_user');
        }
        $isFree = $this->school->classNamesOf($member->id) === [];
        if (!$teacher->isAdmin() && !$isFree && !$this->school->teaches($teacher->id, $member->id)) {
            throw new ActionRefused('school.unknown_user');
        }
        $this->school->addMember($classId, $member->id);
    }

    public function removeMember(User $teacher, int $classId, int $userId): void
    {
        $this->classFor($teacher, $classId);
        $this->school->removeMember($classId, $userId);
    }

    public function setLimitToClass(User $teacher, int $classId, bool $limitToClass): void
    {
        $this->classFor($teacher, $classId);
        $this->school->setLimitToClass($classId, $limitToClass);
    }

    // --- Login cards and passwords (SCH-04) ---

    /**
     * New login cards for the given members of the class: id => one-time token.
     * Each replaces that student's earlier card.
     *
     * @param list<int> $userIds
     * @return array<int, string>
     */
    public function newLoginCards(User $teacher, int $classId, array $userIds): array
    {
        $this->classFor($teacher, $classId);
        $cards = [];
        foreach ($userIds as $userId) {
            if (!$this->school->isMember($classId, $userId) || !$this->isLookedAfterBy($teacher, $userId)) {
                continue;
            }
            $cards[$userId] = $this->tokens->createToken($userId, self::LOGIN_CARD, self::LOGIN_CARD_SECONDS, true);
        }

        return $cards;
    }

    /** Revokes a student's login card (lost card). */
    public function revokeLoginCard(User $teacher, int $classId, int $userId): void
    {
        $this->classFor($teacher, $classId);
        if ($this->school->isMember($classId, $userId) && $this->isLookedAfterBy($teacher, $userId)) {
            $this->tokens->deleteAllOfType($userId, self::LOGIN_CARD);
        }
    }

    /** Sets a student's password (the teacher tells the student). */
    public function setPassword(User $teacher, int $classId, int $userId, string $password): void
    {
        $this->classFor($teacher, $classId);
        $student = $this->users->findById($userId);
        if ($student === null || !$this->school->isMember($classId, $userId) || !$this->isLookedAfterBy($teacher, $userId)) {
            throw new ActionRefused('school.unknown_user');
        }
        try {
            PasswordRules::checkNewPassword($password, $password, $student->username);
        } catch (AccountRefused $refusal) {
            throw new ActionRefused($refusal->languageKey);
        }
        $this->users->setPasswordHash($userId, PasswordRules::hash($password));
    }

    /** Logs in with a login card: the student's id, or null for an unknown or used card. */
    public function useLoginCard(string $token): ?int
    {
        return $this->tokens->useToken($token, self::LOGIN_CARD);
    }

    // --- Helpers ---

    /**
     * Teachers manage only students: never players, other teachers or admins,
     * whose passwords and logins are their own (security review C2-2).
     * Admins manage everyone.
     */
    private function isLookedAfterBy(User $teacher, int $userId): bool
    {
        $member = $this->users->findById($userId);
        if ($member === null) {
            return false;
        }

        return $teacher->isAdmin() || $member->role === User::ROLE_STUDENT;
    }

    /** Letters a user name can't have, and what they become ("Müller" becomes "Mueller"). */
    private const array LETTER_REPLACEMENTS = [
        'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'Ä' => 'Ae', 'Ö' => 'Oe', 'Ü' => 'Ue', 'ß' => 'ss',
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'å' => 'a', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'ø' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ç' => 'c', 'ñ' => 'n', 'Á' => 'A', 'É' => 'E', 'Ó' => 'O', 'Ú' => 'U',
    ];

    /**
     * A user name from a typed name: special letters spelled out, any other
     * character becomes ".", and a number is added while the name is taken.
     */
    private function freeUsername(string $name): string
    {
        $base = preg_replace('/[^A-Za-z0-9_.-]+/', '.', strtr($name, self::LETTER_REPLACEMENTS)) ?? '';
        $base = substr(trim($base, '.-_'), 0, 34);
        if (strlen($base) < 3) {
            $base = 'student' . $base;
        }
        if ($this->wordFilter->blocks($base)) {
            $base = 'student';
        }
        $username = $base;
        $number = 2;
        while ($this->users->usernameExists($username)) {
            $username = $base . $number;
            $number++;
        }
        PasswordRules::checkUsername($username);

        return $username;
    }
}
