<?php

declare(strict_types=1);

namespace PhpChess\Infra\Mail;

/**
 * Sends email (ADM-02). Two ways, chosen on the admin's mail settings page:
 *
 * - LogFileMailer: writes each email to storage/logs/mail.log; for development
 *   and for offline school servers without a mail server;
 * - SmtpMailer: sends through the host's SMTP server, with PHPMailer.
 *
 * Services ask for "a Mailer" and never know which one they have.
 */
interface Mailer
{
    /** Sends one email. Throws MailFailed when it could not be sent. */
    public function send(Email $email): void;
}
