<?php

/**
 * Draws the app icons (UI-05) into public/assets/icons/: a white pawn on
 * the site's green, at the sizes phones and browsers ask for.
 *
 *   icon-192.png, icon-512.png     the home-screen icon (Android, desktop)
 *   icon-maskable-512.png          the same with more room around the pawn,
 *                                  for phones that cut icons into circles
 *   apple-touch-icon.png           180 pixels, for iPhone and iPad
 *   badge-96.png                   the white pawn on clear ground, for the
 *                                  small icon in Android's status bar
 *
 * Needs PHP's GD extension. Run from the project root once, when the
 * picture changes; the PNG files are committed:
 *   php -d extension=gd tools/make-icons.php
 */

declare(strict_types=1);

if (!function_exists('imagecreatetruecolor')) {
    fwrite(STDERR, "This needs PHP's GD extension: php -d extension=gd tools/make-icons.php\n");
    exit(1);
}

$folder = dirname(__DIR__) . '/public/assets/icons';
if (!is_dir($folder)) {
    mkdir($folder, 0775, true);
}

/**
 * Draws one icon.
 *
 * @param int  $size        width and height in pixels
 * @param float $pawnScale  how much of the icon the pawn fills (0 to 1)
 * @param bool $withGround  true: green ground; false: clear (the badge)
 */
function drawIcon(string $path, int $size, float $pawnScale, bool $withGround): void
{
    // Drawn 4 times bigger, then scaled down: smooth edges without anti-aliasing tricks.
    $big = $size * 4;
    $image = imagecreatetruecolor($big, $big);
    imagesavealpha($image, true);
    $clear = (int) imagecolorallocatealpha($image, 0, 0, 0, 127);
    $green = (int) imagecolorallocate($image, 0x2f, 0x6f, 0x4f);
    $white = (int) imagecolorallocate($image, 0xff, 0xff, 0xff);
    imagefill($image, 0, 0, $withGround ? $green : $clear);

    // The pawn, in a box of $pawn pixels in the middle: head, neck, body, base.
    $pawn = $big * $pawnScale;
    $left = ($big - $pawn) / 2;
    $top = ($big - $pawn) / 2;
    $centre = (int) ($big / 2);
    imagefilledellipse($image, $centre, (int) ($top + $pawn * 0.22), (int) ($pawn * 0.36), (int) ($pawn * 0.36), $white);
    imagefilledellipse($image, $centre, (int) ($top + $pawn * 0.44), (int) ($pawn * 0.42), (int) ($pawn * 0.12), $white);
    imagefilledpolygon($image, [
        (int) ($centre - $pawn * 0.13), (int) ($top + $pawn * 0.44),
        (int) ($centre + $pawn * 0.13), (int) ($top + $pawn * 0.44),
        (int) ($centre + $pawn * 0.30), (int) ($top + $pawn * 0.86),
        (int) ($centre - $pawn * 0.30), (int) ($top + $pawn * 0.86),
    ], $white);
    imagefilledrectangle($image, (int) ($left + $pawn * 0.12), (int) ($top + $pawn * 0.84), (int) ($left + $pawn * 0.88), (int) ($top + $pawn * 0.97), $white);

    $small = imagecreatetruecolor($size, $size);
    imagesavealpha($small, true);
    imagealphablending($small, false);
    imagefill($small, 0, 0, (int) imagecolorallocatealpha($small, 0, 0, 0, 127));
    imagecopyresampled($small, $image, 0, 0, 0, 0, $size, $size, $big, $big);
    imagepng($small, $path, 9);
    echo "Wrote {$path}\n";
}

drawIcon($folder . '/icon-192.png', 192, 0.62, true);
drawIcon($folder . '/icon-512.png', 512, 0.62, true);
drawIcon($folder . '/icon-maskable-512.png', 512, 0.46, true);
drawIcon($folder . '/apple-touch-icon.png', 180, 0.62, true);
drawIcon($folder . '/badge-96.png', 96, 0.86, false);
