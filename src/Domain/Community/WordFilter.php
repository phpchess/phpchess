<?php

declare(strict_types=1);

namespace PhpChess\Domain\Community;

use PhpChess\Infra\System\SettingsRepository;

/**
 * The word filter (C-05) for game chat, messages and user names.
 *
 * The admin keeps the list on the community settings page, one word per
 * line (setting "community.word_filter"). In chat and messages each
 * listed word is replaced by stars, whatever its case. A user name that
 * contains a listed word is refused.
 */
final class WordFilter
{
    public const string SETTING = 'community.word_filter';

    /** @var list<string> the words, in lower case */
    private readonly array $words;

    /**
     * @param list<string> $words
     */
    public function __construct(array $words)
    {
        $cleanWords = [];
        foreach ($words as $word) {
            $word = mb_strtolower(trim($word));
            if ($word !== '') {
                $cleanWords[] = $word;
            }
        }
        $this->words = $cleanWords;
    }

    /** The filter with the admin's list. */
    public static function fromSettings(SettingsRepository $settings): self
    {
        $lines = preg_split('/\R/', $settings->getString(self::SETTING));

        return new self($lines === false ? [] : $lines);
    }

    /** The text with every listed word replaced by stars. */
    public function clean(string $text): string
    {
        foreach ($this->words as $word) {
            $pattern = '/' . preg_quote($word, '/') . '/iu';
            $stars = str_repeat('*', mb_strlen($word));
            $text = (string) preg_replace($pattern, $stars, $text);
        }

        return $text;
    }

    /** True when the text (a user name, say) contains a listed word. */
    public function blocks(string $text): bool
    {
        $lowerText = mb_strtolower($text);
        foreach ($this->words as $word) {
            if (str_contains($lowerText, $word)) {
                return true;
            }
        }

        return false;
    }
}
