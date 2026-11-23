<?php

declare(strict_types=1);

namespace PhpChess\Infra\Mail;

use PHPMailer\PHPMailer\Exception as PhpMailerException;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Sends email through an SMTP server, using the PHPMailer library (ADM-02, LIBRARIES.md).
 *
 * The server details come from the admin's mail settings. PHPMailer handles
 * the SMTP conversation, encryption (TLS/SSL) and correct email headers;
 * this class only fills it in.
 */
final class SmtpMailer implements Mailer
{
    /**
     * @param string $encryption  "tls" (STARTTLS, usually port 587), "ssl" (usually 465) or "none"
     */
    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $username,
        private readonly string $password,
        private readonly string $encryption,
        private readonly string $fromAddress,
        private readonly string $fromName,
    ) {
    }

    public function send(Email $email): void
    {
        $mailer = new PHPMailer(true);
        try {
            $mailer->isSMTP();
            $mailer->Host = $this->host;
            $mailer->Port = $this->port;
            $mailer->Timeout = 15;
            $mailer->CharSet = PHPMailer::CHARSET_UTF8;

            if ($this->username !== '') {
                $mailer->SMTPAuth = true;
                $mailer->Username = $this->username;
                $mailer->Password = $this->password;
            }
            $mailer->SMTPSecure = match ($this->encryption) {
                'ssl' => PHPMailer::ENCRYPTION_SMTPS,
                'tls' => PHPMailer::ENCRYPTION_STARTTLS,
                default => '',
            };
            $mailer->SMTPAutoTLS = $this->encryption !== 'none';

            $mailer->setFrom($this->fromAddress, $this->fromName);
            $mailer->addAddress($email->toAddress, $email->toName);
            $mailer->Subject = $email->subject;
            $mailer->Body = $email->text;
            $mailer->isHTML(false);

            $mailer->send();
        } catch (PhpMailerException $error) {
            throw new MailFailed('The mail server did not accept the email: ' . $mailer->ErrorInfo, 0, $error);
        }
    }
}
