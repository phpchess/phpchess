<?php

/**
 * Picks the puzzles for the strength test (B15, A59, A60).
 *
 * Reads the full Lichess puzzle database (CC0, about 5 million lines) from
 * standard input and writes a small, fixed selection to
 * src/Domain/Strength/strength-puzzles.csv. Development only: the big file
 * is never in the repository, the small one is.
 *
 * Download lichess_db_puzzle.csv.zst from https://database.lichess.org/
 * into its own folder, then run from the project root:
 *
 *   zstd -dc <folder>/lichess_db_puzzle.csv.zst | php tools/pick-puzzles.php
 *
 * What makes a good test puzzle:
 *   - a reliable rating (rating deviation 80 or less, played 1000+ times);
 *   - liked by players (popularity 85 or more);
 *   - short: the player makes one or two moves (Moves has 2 or 4 entries,
 *     the first is the opponent's move that sets up the puzzle).
 * For each rating band (600, 700, ... 2200) the first $puzzlesPerBand
 * puzzles in the file that pass are kept, so a re-run gives the same list.
 */

declare(strict_types=1);

// --- Settings ---

$lowestBand = 600;
$highestBand = 2200;
$bandWidth = 100;
$puzzlesPerBand = 4;
$outputFile = __DIR__ . '/../src/Domain/Strength/strength-puzzles.csv';

// Column numbers in the Lichess file:
// PuzzleId,FEN,Moves,Rating,RatingDeviation,Popularity,NbPlays,Themes,GameUrl,OpeningTags
$columnId = 0;
$columnFen = 1;
$columnMoves = 2;
$columnRating = 3;
$columnDeviation = 4;
$columnPopularity = 5;
$columnPlays = 6;
$columnThemes = 7;

// --- Read and pick ---

/** @var array<int, list<array<int, string>>> $picked  band => rows */
$picked = [];
$neededTotal = (int) (($highestBand - $lowestBand) / $bandWidth + 1) * $puzzlesPerBand;
$pickedTotal = 0;

$input = fopen('php://stdin', 'r');
if ($input === false) {
    fwrite(STDERR, "Cannot read standard input.\n");
    exit(1);
}

fgetcsv($input, null, ',', '"', ''); // skip the header line

while (($row = fgetcsv($input, null, ',', '"', '')) !== false && $pickedTotal < $neededTotal) {
    if (count($row) < 8) {
        continue;
    }

    $rating = (int) $row[$columnRating];
    $band = intdiv($rating, $bandWidth) * $bandWidth;
    if ($band < $lowestBand || $band > $highestBand) {
        continue;
    }
    if (count($picked[$band] ?? []) >= $puzzlesPerBand) {
        continue;
    }

    $moveCount = count(explode(' ', (string) $row[$columnMoves]));
    $isReliable = (int) $row[$columnDeviation] <= 80 && (int) $row[$columnPlays] >= 1000;
    $isLiked = (int) $row[$columnPopularity] >= 85;
    $isShort = $moveCount === 2 || $moveCount === 4;

    if ($isReliable && $isLiked && $isShort) {
        $picked[$band][] = [
            (string) $row[$columnId],
            (string) $row[$columnFen],
            (string) $row[$columnMoves],
            (string) $rating,
            (string) $row[$columnThemes],
        ];
        $pickedTotal++;
    }
}

// --- Write ---

ksort($picked);
$output = fopen($outputFile, 'w');
if ($output === false) {
    fwrite(STDERR, "Cannot write $outputFile.\n");
    exit(1);
}

fwrite($output, "# Strength test puzzles (B15). Source: Lichess puzzle database, CC0, https://database.lichess.org/\n");
fwrite($output, "# Made by tools/pick-puzzles.php. Moves: the first is the opponent's, then the player's answers (UCI).\n");
fputcsv($output, ['id', 'fen', 'moves', 'rating', 'themes'], ',', '"', '');
foreach ($picked as $rows) {
    foreach ($rows as $row) {
        fputcsv($output, $row, ',', '"', '');
    }
}
fclose($output);

echo "Picked $pickedTotal puzzles into $outputFile\n";
