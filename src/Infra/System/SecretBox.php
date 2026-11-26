<?php

declare(strict_types=1);

namespace PhpChess\Infra\System;

/**
 * Keeps small secrets encrypted, such as the proxy password (A94).
 *
 * A login password is only ever checked, so it is hashed. A proxy password
 * must be sent to the proxy, so it has to be turned back into plain text:
 * that needs encryption, not a hash.
 *
 * How (PHP's OpenSSL extension, present on practically every host; the
 * sodium extension often is not, e.g. on XAMPP):
 *   - AES-256-GCM: encryption with a built-in check (the "tag"), so a
 *     changed text is noticed instead of read wrongly;
 *   - a 256-bit key, made with random_bytes() the first time it is needed
 *     and kept in config/secret.key, never in the database: a stolen
 *     database backup alone reveals nothing;
 *   - a new random nonce for every encryption, stored with the text.
 *
 * A stored secret looks like "sb1:" followed by base64 (nonce + tag + encrypted text).
 */
final class SecretBox
{
    private const string PREFIX = 'sb1:';
    private const string CIPHER = 'aes-256-gcm';
    private const int KEY_BYTES = 32;
    private const int NONCE_BYTES = 12;
    private const int TAG_BYTES = 16;

    /** @param string $keyFile  the key's file, normally <install>/config/secret.key */
    public function __construct(private readonly string $keyFile)
    {
    }

    /** The secret, encrypted, ready to store. Makes the key on first use. */
    public function encrypt(string $plainText): string
    {
        $nonce = random_bytes(self::NONCE_BYTES);
        $tag = '';
        $encrypted = openssl_encrypt($plainText, self::CIPHER, $this->keyMakingItIfNeeded(), OPENSSL_RAW_DATA, $nonce, $tag, '', self::TAG_BYTES);
        if ($encrypted === false) {
            throw new \RuntimeException('The secret could not be encrypted.');
        }

        return self::PREFIX . base64_encode($nonce . $tag . $encrypted);
    }

    /**
     * The secret back as plain text, or null when it can't be read: the key
     * is missing or different (the site was moved without its config
     * folder), or the stored text was changed.
     */
    public function decrypt(string $stored): ?string
    {
        $key = $this->existingKey();
        if ($key === null || !str_starts_with($stored, self::PREFIX)) {
            return null;
        }
        $bytes = base64_decode(substr($stored, strlen(self::PREFIX)), true);
        if ($bytes === false || strlen($bytes) < self::NONCE_BYTES + self::TAG_BYTES) {
            return null;
        }
        $nonce = substr($bytes, 0, self::NONCE_BYTES);
        $tag = substr($bytes, self::NONCE_BYTES, self::TAG_BYTES);
        $encrypted = substr($bytes, self::NONCE_BYTES + self::TAG_BYTES);
        $plainText = openssl_decrypt($encrypted, self::CIPHER, $key, OPENSSL_RAW_DATA, $nonce, $tag);

        return $plainText === false ? null : $plainText;
    }

    // --- The key ---

    private function existingKey(): ?string
    {
        if (!is_file($this->keyFile)) {
            return null;
        }
        $key = base64_decode(trim((string) file_get_contents($this->keyFile)), true);

        return $key !== false && strlen($key) === self::KEY_BYTES ? $key : null;
    }

    private function keyMakingItIfNeeded(): string
    {
        $key = $this->existingKey();
        if ($key !== null) {
            return $key;
        }
        $key = random_bytes(self::KEY_BYTES);
        if (file_put_contents($this->keyFile, base64_encode($key) . "\n", LOCK_EX) === false) {
            throw new \RuntimeException('The key file ' . basename($this->keyFile) . ' could not be written.');
        }
        chmod($this->keyFile, 0600);

        return $key;
    }
}
