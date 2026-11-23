<?php

declare(strict_types=1);

namespace PhpChess\Tests\Support;

use PhpChess\Infra\Mail\Email;
use PhpChess\Infra\Mail\MailFailed;
use PhpChess\Infra\Mail\Mailer;

/**
 * A test mailer: keeps every email in a list instead of sending it, so a test
 * can read the link inside (verification, password reset). Can be told to fail.
 */
final class RecordingMailer implements Mailer
{
    /** @var list<Email> */
    public array $sent = [];

    public bool $fails = false;

    public function send(Email $email): void
    {
        if ($this->fails) {
            throw new MailFailed('The test mailer was told to fail.');
        }
        $this->sent[] = $email;
    }

    /** The token from the link in the newest email, for example ".../verify-email?token=abc". */
    public function lastToken(): string
    {
        $last = $this->sent[count($this->sent) - 1] ?? null;
        if ($last === null || preg_match('/token=([0-9a-f]{64})/', $last->text, $found) !== 1) {
            return '';
        }

        return $found[1];
    }
}
