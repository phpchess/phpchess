<?php

declare(strict_types=1);

namespace PhpChess\Tests\Http;

use PhpChess\Http\QrCode;
use PHPUnit\Framework\TestCase;

/**
 * Tests the QR code drawer for invite links (G-21).
 */
final class QrCodeTest extends TestCase
{
    /** The worked example "HELLO WORLD", version 1-M, from the QR tutorial at thonky.com. */
    public function testTheErrorCorrectionMatchesTheWorkedExample(): void
    {
        $data = [32, 91, 11, 120, 209, 114, 220, 77, 67, 64, 236, 17, 236, 17, 236, 17];

        self::assertSame([196, 35, 39, 119, 235, 215, 231, 226, 93, 23], QrCode::errorCorrectionCodewords($data, 10));
    }

    public function testAnInviteLinkFitsAndHasFinderPatterns(): void
    {
        $matrix = QrCode::matrix('https://chess.example.org/club/invite/0123456789abcdef0123456789abcdef');
        $size = count($matrix);

        self::assertSame(37, $size); // version 5 for 70 characters
        foreach ([[0, 0], [0, $size - 7], [$size - 7, 0]] as [$top, $left]) {
            self::assertTrue($matrix[$top][$left]);
            self::assertFalse($matrix[$top + 1][$left + 1]);
            self::assertTrue($matrix[$top + 3][$left + 3]);
        }
        self::assertTrue($matrix[$size - 8][8], 'the dark module');
    }

    public function testTheSvgIsAnImage(): void
    {
        self::assertStringStartsWith('<svg xmlns="http://www.w3.org/2000/svg"', QrCode::svg('https://example.org/'));
    }
}
