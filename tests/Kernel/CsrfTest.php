<?php

declare(strict_types=1);

namespace PhpChess\Tests\Kernel;

use PhpChess\Http\Request;
use PhpChess\Kernel\Csrf;
use PhpChess\Kernel\Session;
use PHPUnit\Framework\TestCase;

/**
 * Tests the CSRF token (contract K7): accepted from the form or the header,
 * refused when missing, wrong, or from another session.
 */
final class CsrfTest extends TestCase
{
    public function testTokenStaysTheSameWithinASession(): void
    {
        $csrf = new Csrf(Session::inMemory());

        self::assertSame($csrf->token(), $csrf->token());
        self::assertSame(64, strlen($csrf->token()));
    }

    public function testAcceptsTheTokenFromTheForm(): void
    {
        $csrf = new Csrf(Session::inMemory());
        $request = new Request('POST', '/x', form: [Csrf::FORM_FIELD => $csrf->token()]);

        self::assertTrue($csrf->isValidRequest($request));
    }

    public function testAcceptsTheTokenFromTheHeader(): void
    {
        $csrf = new Csrf(Session::inMemory());
        $request = new Request('POST', '/x', headers: ['x-csrf-token' => $csrf->token()]);

        self::assertTrue($csrf->isValidRequest($request));
    }

    public function testRefusesAMissingToken(): void
    {
        $csrf = new Csrf(Session::inMemory());
        $csrf->token();

        self::assertFalse($csrf->isValidRequest(new Request('POST', '/x')));
    }

    public function testRefusesAWrongToken(): void
    {
        $csrf = new Csrf(Session::inMemory());
        $csrf->token();
        $request = new Request('POST', '/x', form: [Csrf::FORM_FIELD => str_repeat('a', 64)]);

        self::assertFalse($csrf->isValidRequest($request));
    }

    public function testRefusesAnotherSessionsToken(): void
    {
        $otherToken = (new Csrf(Session::inMemory()))->token();
        $csrf = new Csrf(Session::inMemory());
        $csrf->token();

        self::assertFalse($csrf->isValidRequest(new Request('POST', '/x', form: [Csrf::FORM_FIELD => $otherToken])));
    }

    public function testRefusesWhenTheSessionHasNoTokenYet(): void
    {
        $csrf = new Csrf(Session::inMemory());

        self::assertFalse($csrf->isValidRequest(new Request('POST', '/x', form: [Csrf::FORM_FIELD => ''])));
    }
}
