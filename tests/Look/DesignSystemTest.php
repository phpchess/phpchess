<?php

declare(strict_types=1);

namespace PhpChess\Tests\Look;

use PhpChess\Http\Template;
use PHPUnit\Framework\TestCase;

/**
 * The F2 design system (A67, A82): icons drawn by code, and no text
 * smaller than 11 px in any style sheet.
 */
final class DesignSystemTest extends TestCase
{
    private const string ROOT = __DIR__ . '/../..';

    /** The smallest text allowed, in pixels (A82). */
    private const float SMALLEST_TEXT_PX = 11.0;

    // --- Icons (A67) ---

    public function testAnIconIsInlineSvgInTheTextColour(): void
    {
        $html = (new Template(self::ROOT . '/templates'))->render('partials/icon', ['name' => 'computer']);

        self::assertStringContainsString('<svg class="icon icon-computer"', $html);
        self::assertStringContainsString('stroke="currentColor"', $html);
        self::assertStringContainsString('aria-hidden="true"', $html);
        self::assertStringContainsString('width="24"', $html);
    }

    public function testAnUnknownIconIsAnEmptyCircleAndItsNameIsEscaped(): void
    {
        $html = (new Template(self::ROOT . '/templates'))->render('partials/icon', ['name' => '"><script>', 'size' => 40]);

        self::assertStringContainsString('<circle cx="12" cy="12" r="8"/>', $html);
        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('width="40"', $html);
    }

    // --- Text sizes (A82) ---

    public function testNoStyleSheetSetsTextBelowElevenPixels(): void
    {
        $files = glob(self::ROOT . '/public/assets/css/*.css');
        self::assertNotFalse($files);
        self::assertNotSame([], $files);

        $tooSmall = [];
        foreach ($files as $file) {
            $css = (string) file_get_contents($file);
            preg_match_all('/font-size:\s*([0-9.]+)(px|rem|em)\b/', $css, $sizes, PREG_SET_ORDER);
            foreach ($sizes as [$whole, $number, $unit]) {
                // rem and em are counted from the normal 16 px text.
                $pixels = $unit === 'px' ? (float) $number : (float) $number * 16;
                if ($pixels < self::SMALLEST_TEXT_PX) {
                    $tooSmall[] = basename($file) . ': ' . $whole;
                }
            }
        }

        self::assertSame([], $tooSmall);
    }
}
