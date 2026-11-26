<?php

declare(strict_types=1);

namespace PhpChess\Domain\Community;

use PhpChess\Domain\ActionRefused;
use PhpChess\Infra\System\SettingsRepository;

/**
 * The word filter (C-05, B36) for game chat, messages, profiles, user
 * names and outside games.
 *
 * The admin keeps the list on the community settings page, one word per
 * line (setting "community.word_filter"). Until the admin saves a list,
 * the starter list shipped with phpChess is used (starter-words.txt).
 *
 * What happens to a post with a listed word is the admin's choice (mode):
 *   off     the list is not used for posts
 *   stars   each listed word becomes stars (the default)
 *   refuse  the whole post is refused with a friendly message
 * User names, class names and outside games are always refused when they
 * contain a listed word (protection of minors, A81).
 *
 * How a list line matches (A91), whatever the case:
 *   - a whole word only: "ass" finds "ass" but not "class" or "pass";
 *   - "|" gives other spellings: "jerk off|jerkoff";
 *   - "*" lets the letter before it repeat: "fu*ck" also finds "fuuuck";
 *   - lines starting with "#" are notes, not words.
 * blocks() (names, outside games) is stricter: a word of MIN_PART_LENGTH
 * letters or more is also found inside other text ("mybadword1"), and
 * names are split where a capital letter or a digit starts ("BadwordKid").
 */
final class WordFilter
{
    public const string SETTING = 'community.word_filter';
    public const string MODE_SETTING = 'community.word_filter_mode';

    public const string MODE_OFF = 'off';
    public const string MODE_STARS = 'stars';
    public const string MODE_REFUSE = 'refuse';
    public const array MODES = [self::MODE_OFF, self::MODE_STARS, self::MODE_REFUSE];

    /** The list used until the admin saves their own, one word per line. */
    public const string STARTER_FILE = __DIR__ . '/starter-words.txt';

    /** What getString() answers while the admin has never saved a list. */
    private const string NOT_SAVED = '(not saved)';

    /** A listed word this long or longer is also found inside other words, in blocks(). */
    public const int MIN_PART_LENGTH = 5;

    /** @var list<string> one regular expression per list line, for whole words */
    private readonly array $wholeWordPatterns;

    /** @var list<string> the same, without word edges, for the long words only */
    private readonly array $partPatterns;

    /**
     * @param list<string> $lines  the list, one word (or "a|b", "fu*ck") per line
     */
    public function __construct(array $lines, private readonly string $mode = self::MODE_STARS)
    {
        $wholeWordPatterns = [];
        $partPatterns = [];
        foreach ($lines as $line) {
            $line = mb_strtolower(trim($line));
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            foreach (explode('|', $line) as $spelling) {
                $spelling = trim($spelling);
                if ($spelling === '' || $spelling === '*') {
                    continue;
                }
                $body = self::patternBody($spelling);
                // Not a letter or digit before or after: a whole word.
                $wholeWordPatterns[] = '/(?<![\p{L}\p{N}])' . $body . '(?![\p{L}\p{N}])/iu';
                if (mb_strlen(str_replace('*', '', $spelling)) >= self::MIN_PART_LENGTH) {
                    $partPatterns[] = '/' . $body . '/iu';
                }
            }
        }
        $this->wholeWordPatterns = $wholeWordPatterns;
        $this->partPatterns = $partPatterns;
    }

    /**
     * One spelling as a piece of a regular expression: every character as
     * itself, except "*", which lets the character before it repeat.
     */
    private static function patternBody(string $spelling): string
    {
        $body = '';
        foreach (mb_str_split($spelling) as $character) {
            $body .= $character === '*' ? '+' : preg_quote($character, '/');
        }

        return ltrim($body, '+');
    }

    /** The filter with the admin's list (or the starter list) and mode. */
    public static function fromSettings(SettingsRepository $settings): self
    {
        $list = $settings->getString(self::SETTING, self::NOT_SAVED);
        if ($list === self::NOT_SAVED) {
            $list = self::starterList();
        }
        $lines = preg_split('/\R/', $list);
        $mode = $settings->getString(self::MODE_SETTING, self::MODE_STARS);

        return new self($lines === false ? [] : $lines, in_array($mode, self::MODES, true) ? $mode : self::MODE_STARS);
    }

    /** The starter list shipped with phpChess, or nothing if the file is missing. */
    public static function starterList(): string
    {
        return is_file(self::STARTER_FILE) ? (string) file_get_contents(self::STARTER_FILE) : '';
    }

    /**
     * A post (chat line, message, profile text) as the admin's mode wants it:
     * unchanged (off), with stars (stars), or refused (refuse).
     *
     * @throws ActionRefused 'words.refused' in refuse mode when a listed word is in it
     */
    public function forPost(string $text): string
    {
        return match ($this->mode) {
            self::MODE_OFF => $text,
            self::MODE_REFUSE => $this->blocks($text) ? throw new ActionRefused('words.refused') : $text,
            default => $this->clean($text),
        };
    }

    /** The text with every listed word replaced by stars. */
    public function clean(string $text): string
    {
        foreach ($this->wholeWordPatterns as $pattern) {
            $text = (string) preg_replace_callback(
                $pattern,
                static fn (array $match): string => str_repeat('*', mb_strlen($match[0])),
                $text,
            );
        }

        return $text;
    }

    /** True when the text (a user name, a tag, a comment) holds a listed word. */
    public function blocks(string $text): bool
    {
        // "BadwordKid" and "badword1" are read as "Badword Kid" and "badword 1".
        $split = (string) preg_replace('/(?<=\p{Ll})(?=\p{Lu})|(?<=\p{L})(?=\p{N})|(?<=\p{N})(?=\p{L})|_/u', ' ', $text);
        foreach ($this->wholeWordPatterns as $pattern) {
            if (preg_match($pattern, $split) === 1) {
                return true;
            }
        }
        foreach ($this->partPatterns as $pattern) {
            if (preg_match($pattern, $text) === 1) {
                return true;
            }
        }

        return false;
    }
}
