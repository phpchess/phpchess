<?php

declare(strict_types=1);

namespace PhpChess\Domain\System;

/**
 * What this server's PHP can do, for features that need more than the
 * basics. Nobody has to edit php.ini: phpChess tests the server and fits
 * itself to it.
 *
 *   images  PHP's GD extension, to check and re-encode uploaded pictures
 *           (C-02). Without it, "file uploads" stays off; the bundled
 *           avatars still work.
 *   push    openssl (with the P-256 curve), curl and mbstring, for Web
 *           Push (N-03). Without them, "push notifications" stays off;
 *           members still get email.
 *   outside curl, for the Lichess module's requests to lichess.org
 *           (C5-1). Without it, "external content" stays off; the
 *           bundled puzzles and uploaded study games still work.
 *
 * FeatureSwitches asks this class, so a feature the server can't support
 * is off everywhere: menus, pages and the API (K8). The installer's
 * preflight and the admin's switches page show the result.
 *
 * Tests can pretend something is missing with pretendMissing().
 */
final class ServerSupport
{
    public const string IMAGES = 'images';
    public const string PUSH = 'push';
    public const string OUTSIDE = 'outside';

    /** Which feature switch needs which support. */
    public const array NEEDED_BY_SWITCH = [
        FeatureSwitches::FILE_UPLOADS => self::IMAGES,
        FeatureSwitches::PUSH_NOTIFICATIONS => self::PUSH,
        FeatureSwitches::EXTERNAL_CONTENT => self::OUTSIDE,
    ];

    /** @var list<string> supports a test pretends are missing */
    private static array $pretendedMissing = [];

    /** True when the server has what this support needs. */
    public static function has(string $support): bool
    {
        if (in_array($support, self::$pretendedMissing, true)) {
            return false;
        }

        return match ($support) {
            self::IMAGES => function_exists('imagecreatefromstring') && function_exists('imagepng') && function_exists('getimagesizefromstring'),
            self::PUSH => self::hasPushSupport(),
            self::OUTSIDE => function_exists('curl_init'),
            default => false,
        };
    }

    /** The support a switch needs, or null when it needs nothing special. */
    public static function neededBy(string $switchName): ?string
    {
        return self::NEEDED_BY_SWITCH[$switchName] ?? null;
    }

    /** True when the server can run this switch's feature. */
    public static function canRun(string $switchName): bool
    {
        $support = self::neededBy($switchName);

        return $support === null || self::has($support);
    }

    /**
     * For tests only: pretend these supports are missing ([] for the real server).
     *
     * @param list<string> $supports
     */
    public static function pretendMissing(array $supports): void
    {
        self::$pretendedMissing = $supports;
    }

    private static function hasPushSupport(): bool
    {
        foreach (['openssl', 'curl', 'mbstring'] as $extension) {
            if (!extension_loaded($extension)) {
                return false;
            }
        }
        $curves = openssl_get_curve_names();

        return is_array($curves) && in_array('prime256v1', $curves, true);
    }
}
