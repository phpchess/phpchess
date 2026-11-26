<?php

/**
 * Builds the bundled puzzle set (C5-1, L-01, A76, A80).
 *
 * Reads the full Lichess puzzle database (CC0, about 6 million lines, 1.2 GB
 * unpacked) and writes a selection of about 500 KB to
 * src/Domain/Puzzles/puzzle-set.csv. The set ships in the release zip, so
 * puzzles work in an offline school. Development only: the big file is never
 * in the repository and the server never downloads it.
 *
 * Run from the project root, with the unpacked CSV as the argument:
 *
 *   php tools/build-puzzle-set.php <folder>/lichess_db_puzzle.csv
 *
 * What makes a puzzle good enough for the set:
 *   - liked by players: popularity 80 or more;
 *   - a reliable rating: played 1000 times or more;
 *   - a rating from 600 to 2599.
 * Spread: every puzzle gets one "motif" (the first theme of $motifs it has)
 * and one rating band (200 wide). For each motif and band the first
 * $puzzlesPerBucket puzzles in the file that pass are kept, so a re-run gives
 * the same list.
 *
 * Output columns: id, fen, moves, rating, themes, game
 *   id      Lichess puzzle id (link: https://lichess.org/training/<id>)
 *   fen     position before the opponent's first move
 *   moves   UCI moves; the first is the opponent's, the rest the solution
 *   rating  Lichess puzzle rating
 *   themes  space-separated Lichess theme names
 *   game    source game and move, e.g. "787zsVup/black#48"
 *           (link: https://lichess.org/<game>)
 */

declare(strict_types=1);

// --- Settings ---

$lowestRating = 600;
$highestRating = 2599;
$bandWidth = 200;
$puzzlesPerBucket = 14;
$minimumPopularity = 80;
$minimumPlays = 1000;
$outputFile = __DIR__ . '/../src/Domain/Puzzles/puzzle-set.csv';

/**
 * The motifs the set is spread over, most specific first. A puzzle counts
 * for the first one in this list that appears in its themes.
 */
$motifs = [
    'mateIn1', 'mateIn2', 'mateIn3', 'backRankMate', 'smotheredMate',
    'fork', 'pin', 'skewer', 'discoveredAttack', 'doubleCheck',
    'deflection', 'attraction', 'interference', 'clearance', 'xRayAttack',
    'sacrifice', 'hangingPiece', 'trappedPiece', 'promotion', 'zugzwang',
    'pawnEndgame', 'rookEndgame', 'queenEndgame', 'endgame', 'opening',
];

// Column numbers in the Lichess file:
// PuzzleId,FEN,Moves,Rating,RatingDeviation,Popularity,NbPlays,Themes,GameUrl,OpeningTags,DailyDate
$columnId = 0;
$columnFen = 1;
$columnMoves = 2;
$columnRating = 3;
$columnPopularity = 5;
$columnPlays = 6;
$columnThemes = 7;
$columnGameUrl = 8;

// --- Open the input ---

$inputPath = $argv[1] ?? '';
$input = $inputPath === '' ? false : fopen($inputPath, 'r');
if ($input === false) {
    fwrite(STDERR, "Usage: php tools/build-puzzle-set.php <path to lichess_db_puzzle.csv>\n");
    exit(1);
}

fgetcsv($input, null, ',', '"', ''); // skip the header line

// --- Read and pick ---

/** @var array<string, list<list<string>>> $picked  "motif|band" => output rows */
$picked = [];
$bucketCount = count($motifs) * (int) ceil(($highestRating - $lowestRating + 1) / $bandWidth);
$fullBuckets = 0;
$linesRead = 0;

while (($row = fgetcsv($input, null, ',', '"', '')) !== false) {
    $linesRead++;
    if (count($row) <= $columnGameUrl) {
        continue;
    }

    $rating = (int) $row[$columnRating];
    $good = $rating >= $lowestRating
        && $rating <= $highestRating
        && (int) $row[$columnPopularity] >= $minimumPopularity
        && (int) $row[$columnPlays] >= $minimumPlays;
    if (!$good) {
        continue;
    }

    // Find the puzzle's motif: the first listed motif among its themes.
    $themes = explode(' ', (string) $row[$columnThemes]);
    $motif = null;
    foreach ($motifs as $candidate) {
        if (in_array($candidate, $themes, true)) {
            $motif = $candidate;
            break;
        }
    }
    if ($motif === null) {
        continue;
    }

    $band = $lowestRating + intdiv($rating - $lowestRating, $bandWidth) * $bandWidth;
    $bucket = $motif . '|' . $band;
    if (count($picked[$bucket] ?? []) >= $puzzlesPerBucket) {
        continue;
    }

    // Keep the game part of the URL only: "https://lichess.org/game/x/black#48" -> "x/black#48".
    $game = (string) preg_replace('~^https://lichess\.org/(game/)?~', '', (string) $row[$columnGameUrl]);

    $picked[$bucket][] = [
        (string) $row[$columnId],
        (string) $row[$columnFen],
        (string) $row[$columnMoves],
        (string) $rating,
        (string) $row[$columnThemes],
        $game,
    ];
    if (count($picked[$bucket]) === $puzzlesPerBucket) {
        $fullBuckets++;
        if ($fullBuckets === $bucketCount) {
            break; // every bucket is full: no need to read the rest
        }
    }
}
fclose($input);

// --- Write the set, sorted by rating ---

$rows = array_merge(...array_values($picked));
usort($rows, static fn (array $a, array $b): int => (int) $a[3] <=> (int) $b[3]);

$folder = dirname($outputFile);
if (!is_dir($folder)) {
    mkdir($folder, 0775, true);
}
$output = fopen($outputFile, 'w');
if ($output === false) {
    fwrite(STDERR, "Cannot write $outputFile\n");
    exit(1);
}
fputcsv($output, ['id', 'fen', 'moves', 'rating', 'themes', 'game'], ',', '"', '');
foreach ($rows as $outputRow) {
    fputcsv($output, $outputRow, ',', '"', '');
}
fclose($output);

// --- Report ---

echo "Lines read: $linesRead\n";
echo 'Puzzles written: ' . count($rows) . "\n";
echo "Buckets full: $fullBuckets of $bucketCount\n";
echo 'File size: ' . round(filesize($outputFile) / 1024) . " KB\n";
