<?php

declare(strict_types=1);

namespace PhpChess\Tests\Identity;

use PhpChess\Domain\Identity\AccountRefused;
use PhpChess\Domain\Identity\PasswordRules;
use PHPUnit\Framework\TestCase;

/**
 * Tests the common-password check (ASVS 2.1.7, backlog B5): passwords on the
 * local list are refused, in any case, and other passwords are accepted.
 */
final class CommonPasswordTest extends TestCase
{
    public function testListedPasswordIsCommonInAnyCase(): void
    {
        self::assertTrue(PasswordRules::isCommonPassword('password123'));
        self::assertTrue(PasswordRules::isCommonPassword('QWERTYUIOP'));
    }

    public function testUnlistedPasswordIsNotCommon(): void
    {
        self::assertFalse(PasswordRules::isCommonPassword('green-knight-takes-f7'));
    }

    public function testNewCommonPasswordIsRefused(): void
    {
        $this->expectException(AccountRefused::class);
        PasswordRules::checkNewPassword('checkmate123', 'checkmate123', 'alice');
    }

    public function testNewUncommonPasswordIsAccepted(): void
    {
        PasswordRules::checkNewPassword('green-knight-takes-f7', 'green-knight-takes-f7', 'alice');
        $this->addToAssertionCount(1);
    }
}
