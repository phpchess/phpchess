<?php

/**
 * Cuts a tutor's 4 x 4 grid of facial expressions into 16 face pictures.
 *
 * The user supplies one square PNG per tutor (tutor1.png ... tutor4.png),
 * 16 faces in 4 rows of 4, with thin lines between them. Read left to right,
 * top to bottom, the faces are the 16 emotional states in $states below.
 * The future tutor and analysis feature shows the face that fits what it
 * says (a broad smile for a good move, sceptical for a doubtful one, ...).
 *
 * Output: public/assets/faces/computer/tutors/tutorN/NN-state.jpg,
 * 256 x 256 pixels, for example tutors/tutor1/04-broad-smile.jpg.
 *
 * Development only; needs PHP's GD extension. Run from the project root:
 *   php -d extension=gd tools/cut-tutor-faces.php tutor1.png tutor2.png ...
 */

declare(strict_types=1);

// --- Settings ---

/** The 16 states, in grid order (left to right, top to bottom). */
$states = [
    'neutral', 'small-smile', 'medium-smile', 'broad-smile',
    'open-mouthed-smile', 'broad-open-mouthed-smile', 'laugh', 'small-sad',
    'medium-sad', 'very-sad', 'angry', 'little-sceptical',
    'medium-sceptical', 'very-sceptical', 'thoughtful', 'very-thoughtful',
];
$gridSize = 4;
$outputSize = 256;
$jpegQuality = 85;
/** Pixels cut off each side of a cell, so the lines between faces do not show. */
$inset = 6;
$outputFolder = __DIR__ . '/../public/assets/faces/computer/tutors';

// --- Cutting ---

$files = array_slice($argv, 1);
if ($files === []) {
    fwrite(STDERR, "Usage: php -d extension=gd tools/cut-tutor-faces.php tutor1.png [tutor2.png ...]\n");
    exit(1);
}

foreach ($files as $file) {
    $grid = imagecreatefrompng($file);
    if ($grid === false) {
        fwrite(STDERR, "Cannot read $file\n");
        exit(1);
    }
    $tutorName = pathinfo($file, PATHINFO_FILENAME);   // "tutor1"
    $folder = $outputFolder . '/' . $tutorName;
    if (!is_dir($folder)) {
        mkdir($folder, 0775, true);
    }

    $cellWidth = imagesx($grid) / $gridSize;
    $cellHeight = imagesy($grid) / $gridSize;
    foreach ($states as $index => $state) {
        $row = intdiv($index, $gridSize);
        $column = $index % $gridSize;
        $left = (int) round($column * $cellWidth) + $inset;
        $top = (int) round($row * $cellHeight) + $inset;
        $width = (int) floor($cellWidth) - 2 * $inset;
        $height = (int) floor($cellHeight) - 2 * $inset;

        $face = imagecreatetruecolor($outputSize, $outputSize);
        imagecopyresampled($face, $grid, 0, 0, $left, $top, $outputSize, $outputSize, $width, $height);
        $name = sprintf('%s/%02d-%s.jpg', $folder, $index + 1, $state);
        imagejpeg($face, $name, $jpegQuality);
        imagedestroy($face);
    }
    imagedestroy($grid);
    echo "$tutorName: 16 faces in $folder\n";
}
