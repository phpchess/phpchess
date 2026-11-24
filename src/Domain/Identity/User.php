<?php

declare(strict_types=1);

namespace PhpChess\Domain\Identity;

/**
 * One account, as the rest of phpChess sees it (ID-07, ID-08).
 *
 * Built by the UserRepository from a row of the users table. The password
 * hash is not part of it: only the LoginService ever looks at passwords.
 *
 * States (ID-07), one field:
 * - pending:  registered, waiting for email verification and/or admin approval
 * - active:   may log in
 * - disabled: blocked by an admin; may not log in
 * - archived: kept for its games and history; may not log in
 *
 * Roles (ID-08): admin, teacher, student, player.
 */
final class User
{
    public const string STATE_PENDING = 'pending';
    public const string STATE_ACTIVE = 'active';
    public const string STATE_DISABLED = 'disabled';
    public const string STATE_ARCHIVED = 'archived';
    /** Deleted by the member (ID-11): anonymised, never active again; not offered in admin lists. */
    public const string STATE_DELETED = 'deleted';
    /** A computer player's account (AI-03, A53): never logs in, never listed; not offered in admin lists. */
    public const string STATE_COMPUTER = 'computer';

    public const array STATES = [self::STATE_PENDING, self::STATE_ACTIVE, self::STATE_DISABLED, self::STATE_ARCHIVED];

    public const string ROLE_ADMIN = 'admin';
    public const string ROLE_TEACHER = 'teacher';
    public const string ROLE_STUDENT = 'student';
    public const string ROLE_PLAYER = 'player';

    public const array ROLES = [self::ROLE_ADMIN, self::ROLE_TEACHER, self::ROLE_STUDENT, self::ROLE_PLAYER];

    public function __construct(
        public readonly int $id,
        public readonly string $username,
        public readonly string $email,
        public readonly string $role,
        public readonly string $state,
        public readonly bool $isEmailVerified,
        public readonly bool $isApproved,
        public readonly string $createdAt,
        public readonly ?string $lastLoginAt,
    ) {
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isActive(): bool
    {
        return $this->state === self::STATE_ACTIVE;
    }
}
