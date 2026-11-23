<?php

declare(strict_types=1);

namespace PhpChess\Domain\System;

/**
 * The one-time setup token that reopens the locked installer (INS-06).
 *
 * Once installed (config "installed" = true) the installer refuses to run.
 * To open it again, for example to run the migrations of an update, an admin
 * creates a setup token on the admin pages (C1-2), uploads the new release
 * (which brings the installer back), and opens the link. The token works once,
 * for one hour. Only its hash is stored, in storage/setup-token.json, so a
 * leaked storage folder does not give the token away.
 */
final class SetupToken
{
    private const int TOKEN_LIFETIME_SECONDS = 3600;

    public function __construct(private readonly string $appRoot)
    {
    }

    /** Creates a new token, replacing any old one, and returns it (shown to the admin once). */
    public function createSetupToken(int $now): string
    {
        $token = bin2hex(random_bytes(32));
        $record = [
            'hash' => hash('sha256', $token),
            'expires_at' => $now + self::TOKEN_LIFETIME_SECONDS,
        ];
        file_put_contents($this->tokenFile(), json_encode($record, JSON_THROW_ON_ERROR), LOCK_EX);

        return $token;
    }

    /**
     * Checks a token and, when it is right, deletes it so it can't be used again.
     * Returns false for a wrong, expired or already used token.
     */
    public function useSetupToken(string $token, int $now): bool
    {
        $file = $this->tokenFile();
        if ($token === '' || !is_file($file)) {
            return false;
        }

        $record = json_decode((string) file_get_contents($file), true);
        if (!is_array($record) || !is_string($record['hash'] ?? null) || !is_int($record['expires_at'] ?? null)) {
            return false;
        }

        if ($now > $record['expires_at']) {
            unlink($file);

            return false;
        }
        if (!hash_equals($record['hash'], hash('sha256', $token))) {
            return false;
        }

        unlink($file);

        return true;
    }

    private function tokenFile(): string
    {
        return $this->appRoot . '/storage/setup-token.json';
    }
}
