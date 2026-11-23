<?php

declare(strict_types=1);

namespace PhpChess\Tests\Look;

use PhpChess\Domain\Identity\Preferences;
use PhpChess\Domain\Identity\User;
use PhpChess\Domain\Language\Translator;
use PhpChess\Domain\Look\Skin;
use PhpChess\Domain\System\FeatureSwitches;
use PhpChess\Http\Template;
use PhpChess\Tests\Support\TestInstallFolder;
use PhpChess\Domain\Chess\Rules;
use PhpChess\Domain\Notify\MoveEmails;
use PhpChess\Domain\Notify\Notices;
use PhpChess\Domain\Play\Challenge;
use PhpChess\Domain\Play\Game;
use PhpChess\Domain\Play\Games;
use PhpChess\Domain\Rating\Ratings;
use PHPUnit\Framework\TestCase;

/**
 * Tests the language file (UI-06), the skin format and its contrast (UI-01, UI-04),
 * and the admin's template override (UI-07).
 */
final class LanguageAndLookTest extends TestCase
{
    private const string REPOSITORY = __DIR__ . '/../..';

    // --- Language (UI-06) ---

    public function testEveryTextKeyUsedInTheCodeExistsInEnglish(): void
    {
        $translator = new Translator(self::REPOSITORY . '/lang', 'en');
        $missing = [];

        foreach ($this->filesIn(['src', 'templates']) as $file) {
            $code = (string) file_get_contents($file);
            // Keys written out in full: ->text('login.title') and new AccountRefused('login.too_many_tries')
            preg_match_all("/(?:->text|AccountRefused|redirect\([^,]+,\s*'[^']+',\s*'[a-z]+',)\(?\s*'([a-z0-9_]+\.[a-z0-9_.]+)'/", $code, $found);
            foreach ($found[1] as $key) {
                // A key ending in "." is the start of a key built from a list; tested below.
                if (!str_ends_with($key, '.') && !$translator->hasKey($key)) {
                    $missing[] = basename($file) . ': ' . $key;
                }
            }
        }

        self::assertSame([], $missing);
    }

    public function testEveryKeyBuiltFromAListExists(): void
    {
        $translator = new Translator(self::REPOSITORY . '/lang', 'en');
        $keys = [];
        foreach (User::STATES as $state) {
            $keys[] = 'state.' . $state;
        }
        foreach (User::ROLES as $role) {
            $keys[] = 'role.' . $role;
        }
        foreach (Preferences::COLOUR_SCHEMES as $scheme) {
            $keys[] = 'preferences.colour_scheme.' . $scheme;
        }
        foreach (Preferences::BOARD_THEMES as $board) {
            $keys[] = 'preferences.board.' . $board;
        }
        foreach (Preferences::PIECE_SETS as $pieces) {
            $keys[] = 'preferences.pieces.' . $pieces;
        }
        foreach (array_keys(FeatureSwitches::ALL) as $switch) {
            $keys[] = 'switch.' . $switch . '.label';
            $keys[] = 'switch.' . $switch . '.explain';
        }
        foreach (['tls', 'ssl', 'none'] as $encryption) {
            $keys[] = 'admin.mail.encryption.' . $encryption;
        }
        foreach ($translator->availableLanguages() as $language) {
            $keys[] = 'language.' . $language;
        }

        // --- Play (C2-1) ---
        foreach ([Challenge::KIND_OPEN, Challenge::KIND_DIRECT, Challenge::KIND_INVITE] as $kind) {
            $keys[] = 'challenge.yours.' . $kind;
        }
        foreach (Challenge::COLOURS as $colour) {
            $keys[] = 'challenge.colour.' . $colour;
        }
        foreach (MoveEmails::MODES as $mode) {
            $keys[] = 'move_email.' . $mode;
        }
        foreach (Games::ABORT_RULES as $rule) {
            $keys[] = 'admin.play.abort_rule.' . $rule;
        }
        foreach (array_keys(Ratings::STRENGTH_BANDS) as $band) {
            $keys[] = 'register.strength.' . $band;
        }
        foreach ([Game::RESULT_WHITE_WON, Game::RESULT_BLACK_WON, Game::RESULT_DRAW, Game::RESULT_ABORTED] as $result) {
            $keys[] = 'result.' . $result;
        }
        $endReasons = [
            Rules::ENDING_CHECKMATE, Rules::ENDING_STALEMATE, Rules::ENDING_INSUFFICIENT_MATERIAL,
            Rules::ENDING_THREEFOLD_REPETITION, Rules::ENDING_FIFTY_MOVES,
            Games::REASON_RESIGNATION, Games::REASON_DRAW_AGREED, Games::REASON_ABORTED,
            Games::REASON_ABORT_COUNTED_AS_LOSS, Games::REASON_TIMEOUT, Games::REASON_TIMEOUT_DRAW, 'adjudicated',
        ];
        foreach ($endReasons as $reason) {
            $keys[] = 'end_reason.' . $reason;
        }
        $noticeKinds = [
            Notices::YOUR_MOVE, Notices::GAME_ENDED, Notices::DRAW_OFFERED, Notices::REMATCH_OFFERED,
            Notices::CHALLENGE_RECEIVED, Notices::CHALLENGE_ACCEPTED, Notices::ABORT_REQUESTED,
        ];
        foreach ($noticeKinds as $kind) {
            $keys[] = 'notice.' . $kind;
        }
        $keys[] = 'ratings.speed.' . Game::SPEED_CORRESPONDENCE;

        foreach ($keys as $key) {
            self::assertTrue($translator->hasKey($key), $key);
        }
    }

    public function testPlaceholdersAreFilledIn(): void
    {
        $translator = new Translator(self::REPOSITORY . '/lang', 'en');

        self::assertSame('The password needs at least 10 characters.', $translator->text('account.password_too_short', ['length' => 10]));
        self::assertSame('no.such.key', $translator->text('no.such.key'), 'a missing key shows itself');
    }

    public function testAnUnknownLanguageFallsBackToEnglish(): void
    {
        $translator = new Translator(self::REPOSITORY . '/lang', 'xx');

        self::assertSame('Log in', $translator->text('login.title'));
    }

    // --- Skin (UI-01, UI-04) ---

    public function testTheDefaultSkinLoadsAndMakesCssVariables(): void
    {
        $skin = Skin::load(self::REPOSITORY . '/skins', 'default');
        $css = $skin->themeCss();

        self::assertSame('Default', $skin->name);
        self::assertStringContainsString('--colour-accent: #2f6f4f;', $css);
        self::assertStringContainsString('@media (prefers-color-scheme: dark)', $css);
        self::assertStringContainsString(':root[data-colour-scheme="dark"]', $css);
    }

    /**
     * Text must stand out from its background: at least 4.5 : 1, the WCAG AA
     * level for normal text, in both the light and the dark colours (UI-04).
     */
    public function testTheDefaultSkinHasEnoughContrast(): void
    {
        $skin = Skin::load(self::REPOSITORY . '/skins', 'default');
        $pairs = [
            ['text', 'surface'], ['text', 'page_background'], ['muted_text', 'surface'],
            ['accent', 'surface'], ['accent_text', 'accent'],
            ['error', 'error_background'], ['success', 'success_background'], ['text', 'warning_background'],
        ];

        foreach (['light', 'dark'] as $scheme) {
            foreach ($pairs as [$foreground, $background]) {
                $ratio = self::contrastRatio($skin->colours[$scheme][$foreground], $skin->colours[$scheme][$background]);
                self::assertGreaterThanOrEqual(4.5, $ratio, "{$scheme}: {$foreground} on {$background} is only " . round($ratio, 2));
            }
        }
    }

    public function testABrokenSkinIsRefusedWithAClearMessage(): void
    {
        $folder = new TestInstallFolder();
        mkdir($folder->path . '/skins/broken');
        file_put_contents($folder->path . '/skins/broken/skin.json', '{"name": "Broken", "colours": {"light": {"text": "red"}}}');

        try {
            Skin::load($folder->path . '/skins', 'broken');
            self::fail('A broken skin should be refused.');
        } catch (\RuntimeException $error) {
            self::assertStringContainsString('colours.light.text must be a colour', $error->getMessage());
        } finally {
            $folder->remove();
        }
    }

    // --- Template override (UI-07) ---

    public function testATemplateInLocalWinsOverTheCoreOne(): void
    {
        $folder = new TestInstallFolder();
        mkdir($folder->path . '/templates/local');
        file_put_contents($folder->path . '/templates/local/error.php', '<?php /** Override for the test. */ ?>Our own error page');

        try {
            $html = (new Template($folder->path . '/templates'))->render('error', ['status' => 404, 'message' => '', 'basePath' => '']);
        } finally {
            $folder->remove();
        }

        self::assertSame('Our own error page', $html);
    }

    // --- Helpers ---

    /** The WCAG contrast ratio of two colours "#rrggbb". */
    private static function contrastRatio(string $first, string $second): float
    {
        $lighter = max(self::luminance($first), self::luminance($second));
        $darker = min(self::luminance($first), self::luminance($second));

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    private static function luminance(string $colour): float
    {
        $channels = [];
        foreach ([1, 3, 5] as $start) {
            $value = hexdec(substr($colour, $start, 2)) / 255;
            $channels[] = $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    /**
     * @param list<string> $folders
     * @return list<string>
     */
    private function filesIn(array $folders): array
    {
        $files = [];
        foreach ($folders as $folder) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::REPOSITORY . '/' . $folder, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if ($file instanceof \SplFileInfo && $file->getExtension() === 'php') {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }
}
