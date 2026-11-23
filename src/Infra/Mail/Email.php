<?php

declare(strict_types=1);

namespace PhpChess\Infra\Mail;

/**
 * One plain-text email: to whom, subject and text (ID-02, ID-06, ADM-02).
 *
 * phpChess sends plain text only: it reads well on every phone, and no
 * outside images or tracking can be hidden in it (A16).
 */
final class Email
{
    public function __construct(
        public readonly string $toAddress,
        public readonly string $toName,
        public readonly string $subject,
        public readonly string $text,
    ) {
    }
}
