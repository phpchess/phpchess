<?php

declare(strict_types=1);

namespace PhpChess\Domain\Language;

/**
 * Gives every text in the user's language (UI-06).
 *
 * Texts live in lang/<language>.php, one plain array per language:
 *     'login.title' => 'Log in',
 *     'register.welcome' => 'Welcome, {name}!',
 * A key names where the text is used (page.part). Parts in braces are filled
 * in from the values passed to text(). English (lang/en.php) is complete and
 * is the fallback for any key another language doesn't have yet; a key that
 * is missing everywhere is shown as itself, so it is easy to spot.
 */
final class Translator
{
    public const string FALLBACK_LANGUAGE = 'en';

    /** @var array<string, string> */
    private array $texts;

    /** @var array<string, string> */
    private array $fallbackTexts;

    public function __construct(private readonly string $languageFolder, public readonly string $language)
    {
        $this->fallbackTexts = self::loadFile($languageFolder, self::FALLBACK_LANGUAGE);
        $this->texts = $language === self::FALLBACK_LANGUAGE ? $this->fallbackTexts : self::loadFile($languageFolder, $language);
    }

    /**
     * One text, with its {placeholders} filled in.
     *
     * @param array<string, string|int> $values  placeholder name => value
     */
    public function text(string $key, array $values = []): string
    {
        $text = $this->texts[$key] ?? $this->fallbackTexts[$key] ?? $key;

        foreach ($values as $name => $value) {
            $text = str_replace('{' . $name . '}', (string) $value, $text);
        }

        return $text;
    }

    /** True when the key exists in English (used by the language test). */
    public function hasKey(string $key): bool
    {
        return isset($this->fallbackTexts[$key]);
    }

    /**
     * The languages that have a file, such as ["en"].
     *
     * @return list<string>
     */
    public function availableLanguages(): array
    {
        $languages = [];
        foreach (glob($this->languageFolder . '/*.php') ?: [] as $file) {
            $languages[] = basename($file, '.php');
        }
        sort($languages);

        return $languages;
    }

    /**
     * @return array<string, string>
     */
    private static function loadFile(string $folder, string $language): array
    {
        if (preg_match('/^[a-z]{2}(-[A-Z]{2})?$/', $language) !== 1) {
            return [];
        }
        $file = $folder . '/' . $language . '.php';
        if (!is_file($file)) {
            return [];
        }

        $texts = require $file;
        if (!is_array($texts)) {
            return [];
        }

        $clean = [];
        foreach ($texts as $key => $text) {
            if (is_string($key) && is_string($text)) {
                $clean[$key] = $text;
            }
        }

        return $clean;
    }
}
