<?php

declare(strict_types=1);

namespace PhpChess\Infra\Mail;

/**
 * "Sends" email by writing it to storage/logs/mail.log (ADM-02).
 *
 * The default until an admin sets up SMTP, and the right choice on a school
 * server without mail. The links in the emails (verification, password reset)
 * can be copied from the file by the admin.
 */
final class LogFileMailer implements Mailer
{
    public function __construct(
        private readonly string $logFile,
        private readonly string $fromAddress,
    ) {
    }

    public function send(Email $email): void
    {
        $entry = "----- " . gmdate('Y-m-d H:i:s') . " UTC -----\n"
            . "From: {$this->fromAddress}\n"
            . "To: {$email->toName} <{$email->toAddress}>\n"
            . "Subject: {$email->subject}\n\n"
            . $email->text . "\n\n";

        if (@file_put_contents($this->logFile, $entry, FILE_APPEND | LOCK_EX) === false) {
            throw new MailFailed('The mail log file could not be written: ' . $this->logFile);
        }
    }
}
