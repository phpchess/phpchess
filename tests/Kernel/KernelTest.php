<?php

declare(strict_types=1);

namespace PhpChess\Tests\Kernel;

use PhpChess\Kernel\Kernel;
use PhpChess\Kernel\SecurityHeaders;
use PhpChess\Tests\Support\TestBrowser;
use PhpChess\Tests\Support\TestInstallFolder;
use PHPUnit\Framework\TestCase;

/**
 * Tests the kernel without a database: redirects to the installer, method
 * refusal and CSRF (K7), the API error format (API-04), security headers (K9),
 * and that every refusal is a finished response (K13).
 */
final class KernelTest extends TestCase
{
    private TestInstallFolder $folder;
    private TestBrowser $browser;

    protected function setUp(): void
    {
        $this->folder = new TestInstallFolder();
        $this->browser = new TestBrowser(new Kernel($this->folder->path, fetchUrl: fn (string $url): string => 'Forbidden'));
    }

    protected function tearDown(): void
    {
        $this->folder->remove();
    }

    public function testWithoutAConfigEveryPageLeadsToTheInstaller(): void
    {
        $response = $this->browser->get('/');

        self::assertSame(303, $response->status);
        self::assertSame('/chess/install', $response->header('Location'));
        self::assertSame('', $response->body, 'a redirect sends nothing else (K13)');
    }

    public function testTheInstallerStartsWithThePreflightChecks(): void
    {
        $response = $this->browser->get('/install');

        self::assertSame(200, $response->status);
        self::assertStringContainsString('Step 1', $response->body);
        self::assertStringContainsString('/chess/assets/css/base.css', $response->body);
    }

    public function testAPostWithoutTheCsrfTokenIsRefused(): void
    {
        $this->browser->get('/install');

        $response = $this->browser->post('/install/preflight', [], withCsrfToken: false);

        self::assertSame(419, $response->status);
    }

    public function testAPostWithAWrongCsrfTokenIsRefused(): void
    {
        $this->browser->get('/install');

        $response = $this->browser->post('/install/preflight', ['_csrf' => str_repeat('0', 64)]);

        self::assertSame(419, $response->status);
    }

    public function testAPostWithTheCsrfTokenIsAccepted(): void
    {
        $this->browser->get('/install');

        $response = $this->browser->post('/install/preflight');

        self::assertSame(303, $response->status);
    }

    public function testAChangingAddressRefusesGet(): void
    {
        $response = $this->browser->get('/install/finish');

        self::assertSame(405, $response->status);
        self::assertSame('POST', $response->header('Allow'));
    }

    public function testTheApiAnswersErrorsInItsOneFormat(): void
    {
        $response = $this->browser->get('/api/v1/nothing');

        self::assertSame(404, $response->status);
        self::assertStringStartsWith('application/json', $response->header('Content-Type'));
        self::assertSame(
            ['error' => ['code' => 'not_found', 'message' => 'There is no page at this address.']],
            json_decode($response->body, true),
        );
    }

    public function testEveryResponseCarriesTheSecurityHeaders(): void
    {
        foreach (['/', '/install', '/install/finish', '/api/v1/nothing'] as $path) {
            $response = $this->browser->get($path);

            self::assertSame(SecurityHeaders::CONTENT_SECURITY_POLICY, $response->header('Content-Security-Policy'), $path);
            self::assertSame('nosniff', $response->header('X-Content-Type-Options'));
            self::assertSame('DENY', $response->header('X-Frame-Options'));
        }
    }

    public function testThePolicyAllowsNoOutsideRequests(): void
    {
        // K9 / INS-10: every source is 'self'; no outside host is named.
        self::assertStringContainsString("default-src 'self'", SecurityHeaders::CONTENT_SECURITY_POLICY);
        self::assertStringContainsString("connect-src 'self'", SecurityHeaders::CONTENT_SECURITY_POLICY);
        self::assertDoesNotMatchRegularExpression('#https?:|\*#', SecurityHeaders::CONTENT_SECURITY_POLICY);
    }
}
