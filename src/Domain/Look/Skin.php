<?php

declare(strict_types=1);

namespace PhpChess\Domain\Look;

use RuntimeException;

/**
 * One skin: the look of the site, as plain settings (UI-01, A5, A25).
 *
 * A skin is a folder in skins/ with a skin.json file and its images. Nothing
 * in it is code. The settings have plain names (see docs/SKINS.md), so that a
 * simple editor in the admin area (backlog B3) only has to change skin.json:
 *
 *   name, description   shown to the admin
 *   logo                an image file in the skin's folder (svg, png, webp)
 *   layout              how the page is arranged; "top-bar" for now
 *   fonts.text, fonts.headings
 *   board, pieces       the default board colours and piece set for new players
 *   colours.light, colours.dark   one colour per named role (text, surface, accent, ...)
 *
 * themeCss() turns the colours and fonts into CSS variables; every style in
 * public/assets/css/ uses only those variables, so a skin never needs CSS of its own.
 */
final class Skin
{
    /** Every colour a skin names, light and dark. The CSS variable is "--colour-" + name with "-". */
    public const array COLOUR_NAMES = [
        'text', 'muted_text', 'page_background', 'surface', 'border', 'accent', 'accent_text',
        'error', 'error_background', 'warning', 'warning_background', 'success', 'success_background', 'focus',
    ];

    public const array LAYOUTS = ['top-bar'];

    public const array LOGO_TYPES = ['svg' => 'image/svg+xml', 'png' => 'image/png', 'webp' => 'image/webp'];

    /**
     * @param array{light: array<string, string>, dark: array<string, string>} $colours
     */
    public function __construct(
        public readonly string $folderName,
        public readonly string $name,
        public readonly string $description,
        public readonly string $logoFile,
        public readonly string $layout,
        public readonly string $textFont,
        public readonly string $headingFont,
        public readonly string $board,
        public readonly string $pieces,
        public readonly array $colours,
    ) {
    }

    // --- Loading a skin ---

    /** Reads skins/<folderName>/skin.json. Throws RuntimeException naming what is wrong. */
    public static function load(string $skinsFolder, string $folderName): self
    {
        if (preg_match('/^[a-z0-9-]{1,40}$/', $folderName) !== 1) {
            throw new RuntimeException("Not a skin folder name: {$folderName}");
        }
        $file = $skinsFolder . '/' . $folderName . '/skin.json';
        if (!is_file($file)) {
            throw new RuntimeException("The skin \"{$folderName}\" has no skin.json.");
        }

        $settings = json_decode((string) file_get_contents($file), true);
        if (!is_array($settings)) {
            throw new RuntimeException("skins/{$folderName}/skin.json is not valid JSON.");
        }

        $colours = ['light' => [], 'dark' => []];
        foreach (['light', 'dark'] as $scheme) {
            foreach (self::COLOUR_NAMES as $colourName) {
                $value = self::nested($settings, 'colours', $scheme, $colourName);
                if (!is_string($value) || preg_match('/^#[0-9a-fA-F]{6}$/', $value) !== 1) {
                    throw new RuntimeException("skins/{$folderName}/skin.json: colours.{$scheme}.{$colourName} must be a colour like #2f6f4f.");
                }
                $colours[$scheme][$colourName] = strtolower($value);
            }
        }

        $logo = self::text($settings, 'logo');
        $logoExtension = strtolower(pathinfo($logo, PATHINFO_EXTENSION));
        if ($logo !== '' && (basename($logo) !== $logo || !isset(self::LOGO_TYPES[$logoExtension]))) {
            throw new RuntimeException("skins/{$folderName}/skin.json: the logo must be an svg, png or webp file in the skin's folder.");
        }

        $layout = self::text($settings, 'layout');

        return new self(
            folderName: $folderName,
            name: self::text($settings, 'name'),
            description: self::text($settings, 'description'),
            logoFile: $logo,
            layout: in_array($layout, self::LAYOUTS, true) ? $layout : self::LAYOUTS[0],
            textFont: self::font(self::nested($settings, 'fonts', 'text')),
            headingFont: self::font(self::nested($settings, 'fonts', 'headings')),
            board: self::text($settings, 'board'),
            pieces: self::text($settings, 'pieces'),
            colours: $colours,
        );
    }

    /**
     * The skins installed in skins/, folder name => display name.
     *
     * @return array<string, string>
     */
    public static function installedSkins(string $skinsFolder): array
    {
        $skins = [];
        foreach (glob($skinsFolder . '/*/skin.json') ?: [] as $file) {
            $folderName = basename(dirname($file));
            try {
                $skins[$folderName] = self::load($skinsFolder, $folderName)->name;
            } catch (RuntimeException) {
                // A broken skin is left out of the list; the admin page names the problem (B1).
            }
        }
        ksort($skins);

        return $skins;
    }

    // --- The CSS variables ---

    /**
     * The skin's CSS: its colours and fonts as variables. Light by default;
     * dark when the device prefers dark, unless the player chose light; and
     * dark when the player chose dark (data-colour-scheme on <html>, UI-03).
     */
    public function themeCss(): string
    {
        $light = $this->variables('light');
        $dark = $this->variables('dark');

        return "/* Generated from skins/{$this->folderName}/skin.json (UI-01). Change the skin, not this. */\n"
            . ":root {\n{$light}"
            . "    --font-text: {$this->textFont};\n"
            . "    --font-headings: {$this->headingFont};\n"
            . "    color-scheme: light;\n}\n\n"
            . "@media (prefers-color-scheme: dark) {\n"
            . "    :root:not([data-colour-scheme=\"light\"]) {\n" . self::indent($dark) . "        color-scheme: dark;\n    }\n}\n\n"
            . ":root[data-colour-scheme=\"dark\"] {\n{$dark}    color-scheme: dark;\n}\n";
    }

    private function variables(string $scheme): string
    {
        $lines = '';
        foreach ($this->colours[$scheme] as $colourName => $value) {
            $lines .= '    --colour-' . str_replace('_', '-', $colourName) . ": {$value};\n";
        }

        return $lines;
    }

    private static function indent(string $lines): string
    {
        return (string) preg_replace('/^/m', '    ', $lines);
    }

    /**
     * @param array<mixed> $settings
     */
    private static function text(array $settings, string $name): string
    {
        $value = $settings[$name] ?? '';

        return is_string($value) ? trim($value) : '';
    }

    /**
     * Reads a value inside nested settings, for example colours.light.text;
     * null when any part of the way is missing.
     *
     * @param array<mixed> $settings
     */
    private static function nested(array $settings, string ...$keys): mixed
    {
        $value = $settings;
        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }

    /** A font list may only hold letters, digits, spaces, commas, "-" and quotes, so it can't break the CSS. */
    private static function font(mixed $value): string
    {
        $default = 'system-ui, sans-serif';
        if (!is_string($value) || preg_match('/^[A-Za-z0-9 ,"\'-]{1,200}$/', $value) !== 1) {
            return $default;
        }

        return $value;
    }
}
