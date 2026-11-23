<?php

declare(strict_types=1);

namespace PhpChess\Infra\Mail;

use RuntimeException;

/**
 * Thrown when an email could not be sent. The message says why in plain
 * words, so the admin's "send a test email" button can show it.
 */
final class MailFailed extends RuntimeException
{
}
