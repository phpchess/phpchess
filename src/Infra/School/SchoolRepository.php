<?php

declare(strict_types=1);

namespace PhpChess\Infra\School;

use PhpChess\Infra\Repository;

/**
 * The school tables (SCH-02 to SCH-06): school_classes and school_class_members.
 *
 * A class has one teacher and any number of members (students, usually).
 * "limit_to_class" means its students may play and write only within
 * the class (SCH-05); the rule itself is in Domain\School\ClassRules.
 */
final class SchoolRepository extends Repository
{
    // --- Classes ---

    public function createClass(string $name, int $teacherId, bool $limitToClass, int $now): int
    {
        $this->database->run(
            'INSERT INTO ' . $this->table('school_classes') . ' (name, teacher_id, limit_to_class, created_at) VALUES (?, ?, ?, ?)',
            [$name, $teacherId, $limitToClass ? 1 : 0, self::dateTime($now)],
        );

        return $this->database->lastInsertId();
    }

    /**
     * @return array{id: int, name: string, teacher_id: int, limit_to_class: bool}|null
     */
    public function findClass(int $classId): ?array
    {
        $row = $this->database->fetchRow(
            'SELECT id, name, teacher_id, limit_to_class FROM ' . $this->table('school_classes') . ' WHERE id = ?',
            [$classId],
        );

        return $row === null ? null : self::classFromRow($row);
    }

    /**
     * The classes of one teacher, or every class when $teacherId is null (admins).
     *
     * @return list<array{id: int, name: string, teacher_id: int, limit_to_class: bool, member_count: int}>
     */
    public function listClasses(?int $teacherId): array
    {
        $sql = 'SELECT c.id, c.name, c.teacher_id, c.limit_to_class, COUNT(m.user_id) AS member_count'
            . ' FROM ' . $this->table('school_classes') . ' c'
            . ' LEFT JOIN ' . $this->table('school_class_members') . ' m ON m.class_id = c.id';
        $parameters = [];
        if ($teacherId !== null) {
            $sql .= ' WHERE c.teacher_id = ?';
            $parameters[] = $teacherId;
        }
        $rows = $this->database->fetchAll($sql . ' GROUP BY c.id, c.name, c.teacher_id, c.limit_to_class ORDER BY c.name', $parameters);

        return array_map(fn (array $row): array => self::classFromRow($row) + ['member_count' => self::intOf($row['member_count'])], $rows);
    }

    public function setLimitToClass(int $classId, bool $limitToClass): void
    {
        $this->database->run(
            'UPDATE ' . $this->table('school_classes') . ' SET limit_to_class = ? WHERE id = ?',
            [$limitToClass ? 1 : 0, $classId],
        );
    }

    public function deleteClass(int $classId): void
    {
        $this->database->run('DELETE FROM ' . $this->table('school_classes') . ' WHERE id = ?', [$classId]);
    }

    // --- Members ---

    public function addMember(int $classId, int $userId): void
    {
        $this->database->run(
            'INSERT IGNORE INTO ' . $this->table('school_class_members') . ' (class_id, user_id) VALUES (?, ?)',
            [$classId, $userId],
        );
    }

    public function removeMember(int $classId, int $userId): void
    {
        $this->database->run(
            'DELETE FROM ' . $this->table('school_class_members') . ' WHERE class_id = ? AND user_id = ?',
            [$classId, $userId],
        );
    }

    /**
     * The members of a class, by name.
     *
     * @return list<array{id: int, username: string, role: string, last_seen_at: int|null}>
     */
    public function members(int $classId): array
    {
        $rows = $this->database->fetchAll(
            'SELECT u.id, u.username, u.role, u.last_seen_at FROM ' . $this->table('school_class_members') . ' m'
            . ' JOIN ' . $this->table('users') . ' u ON u.id = m.user_id WHERE m.class_id = ? ORDER BY u.username',
            [$classId],
        );

        return array_map(fn (array $row): array => [
            'id' => self::intOf($row['id']),
            'username' => self::textOf($row['username']),
            'role' => self::textOf($row['role']),
            'last_seen_at' => self::timestampOrNull($row['last_seen_at']),
        ], $rows);
    }

    public function isMember(int $classId, int $userId): bool
    {
        return $this->database->fetchValue(
            'SELECT 1 FROM ' . $this->table('school_class_members') . ' WHERE class_id = ? AND user_id = ?',
            [$classId, $userId],
        ) !== null;
    }

    // --- Questions for the class rules (SCH-05, SCH-06) ---

    /** True when the user is a member of at least one class limited to the class. */
    public function isInLimitedClass(int $userId): bool
    {
        return $this->database->fetchValue(
            'SELECT 1 FROM ' . $this->table('school_class_members') . ' m'
            . ' JOIN ' . $this->table('school_classes') . ' c ON c.id = m.class_id'
            . ' WHERE m.user_id = ? AND c.limit_to_class = 1 LIMIT 1',
            [$userId],
        ) !== null;
    }

    /** True when both users are members of the same class, or one teaches a class the other is in. */
    public function belongTogether(int $userId, int $otherUserId): bool
    {
        $sharedClass = $this->database->fetchValue(
            'SELECT 1 FROM ' . $this->table('school_class_members') . ' a'
            . ' JOIN ' . $this->table('school_class_members') . ' b ON b.class_id = a.class_id'
            . ' WHERE a.user_id = ? AND b.user_id = ? LIMIT 1',
            [$userId, $otherUserId],
        );

        return $sharedClass !== null || $this->teaches($userId, $otherUserId) || $this->teaches($otherUserId, $userId);
    }

    /** True when $teacherId is the teacher of a class that $studentId is a member of. */
    public function teaches(int $teacherId, int $studentId): bool
    {
        return $this->database->fetchValue(
            'SELECT 1 FROM ' . $this->table('school_classes') . ' c'
            . ' JOIN ' . $this->table('school_class_members') . ' m ON m.class_id = c.id'
            . ' WHERE c.teacher_id = ? AND m.user_id = ? LIMIT 1',
            [$teacherId, $studentId],
        ) !== null;
    }

    /**
     * The class names a user belongs to (for the data export, ID-11).
     *
     * @return list<string>
     */
    public function classNamesOf(int $userId): array
    {
        $rows = $this->database->fetchAll(
            'SELECT c.name FROM ' . $this->table('school_classes') . ' c'
            . ' JOIN ' . $this->table('school_class_members') . ' m ON m.class_id = c.id WHERE m.user_id = ? ORDER BY c.name',
            [$userId],
        );

        return array_map(fn (array $row): string => self::textOf($row['name']), $rows);
    }

    /**
     * @param array<string, mixed> $row
     * @return array{id: int, name: string, teacher_id: int, limit_to_class: bool}
     */
    private static function classFromRow(array $row): array
    {
        return [
            'id' => self::intOf($row['id']),
            'name' => self::textOf($row['name']),
            'teacher_id' => self::intOf($row['teacher_id']),
            'limit_to_class' => self::intOf($row['limit_to_class']) === 1,
        ];
    }
}
