<?php

/**
 * Builds the starter word list for the word filter (B36, A89, A91).
 *
 * phpChess ships this list so a new site filters bad words from the first
 * day; the admin can change it on the community settings page. It is made
 * from two free lists (both MIT licence), downloaded once by a developer:
 *
 *   - dsojevic/profanity-list, file en.json
 *     https://github.com/dsojevic/profanity-list  (Copyright (c) 2021 David Sojevic)
 *   - coffee-and-fun/google-profanity-words, file data/en.txt
 *     https://github.com/coffee-and-fun/google-profanity-words
 *     (Copyright (c) Robert James Gabriel / Coffee & Fun LLC)
 *
 * The server never downloads anything (A16). Run from the project root:
 *
 *   php tools/build-word-list.php <folder>/en.json <folder>/google-en.txt
 *
 * What is left out (A91), so that ordinary words and children's names pass:
 *   - entries rated "mild" (severity 1) in en.json, such as
 *     "hell", "bloody", "crap";
 *   - entries tagged "religious" in en.json, such as "jesus", "christ", "god damn"
 *     (Jesús and Christian are names);
 *   - ordinary words, chess words and names found in the lists ($neverList
 *     below), like "pawn", "domination", "escort", "Guido", "Wang";
 *   - from the Google list: the same words, words shorter than 3 letters,
 *     and lines with signs other than letters, digits, spaces and "-"
 *     (like "b!tch"; the filter already reads "*" as a repeat sign).
 *
 * Output: src/Domain/Community/starter-words.txt, one entry per line,
 * written as WordFilter reads it ("a|b" for other spellings, "*" for a
 * repeated letter), with the two licence notices at the top as "#" lines.
 */

declare(strict_types=1);

// --- The two input files ---

if ($argc !== 3 || !is_file($argv[1]) || !is_file($argv[2])) {
    fwrite(STDERR, "Usage: php tools/build-word-list.php <en.json> <google-en.txt>\n");
    exit(1);
}
$dsojevicEntries = json_decode((string) file_get_contents($argv[1]), true);
if (!is_array($dsojevicEntries)) {
    fwrite(STDERR, "en.json is not a JSON list.\n");
    exit(1);
}
$googleLines = preg_split('/\R/', (string) file_get_contents($argv[2])) ?: [];

// --- Words that stay out whatever the lists say (A91) ---
// Chess words, everyday words, names and surnames, and words of other
// languages. The admin can still add any of them on the settings page.
$neverList = [
    // chess and everyday
    'pawn', 'domination', 'escort', 'fool', 'dumb', 'jerk', 'lust', 'nude', 'knob', 'butt', 'bum',
    'suck', 'sucks', 'sucker', 'muff', 'fart', 'poo', 'poop', 'tit', 'hoe', 'dink', 'dinks', 'dike',
    'balls', 'flange', 'pansy', 'sissy', 'flamer', 'hardcore', 'topless', 'playboy',
    // names and surnames
    'guido', 'willy', 'willies', 'pollock', 'hooker', 'cox', 'wang', 'kummer', 'cummer', 'santorum', 'lolita',
    // words of other languages
    'negro', 'punta', 'homo', 'pis', 'leso', 'haji', 'ruski', 'gringo', 'mutha', 'muther',
];

// --- 1. dsojevic: keep everything but "mild", "religious" and the never-list ---

$kept = [];            // the lines of the new list
$leftOut = [];         // the spellings left out, so the Google list skips them too
$alreadyListed = [];   // every spelling kept, to skip doubles
foreach ($neverList as $word) {
    $leftOut[$word] = true;
}

foreach ($dsojevicEntries as $entry) {
    if (!is_array($entry) || !is_string($entry['match'] ?? null)) {
        continue;
    }
    $spellings = explode('|', strtolower($entry['match']));
    $tags = is_array($entry['tags'] ?? null) ? $entry['tags'] : [];
    $isMild = ($entry['severity'] ?? 3) === 1;
    $isReligious = in_array('religious', $tags, true);

    $keptSpellings = [];
    foreach ($spellings as $spelling) {
        // The plain form, without repeat signs: "chri*st" is "christ".
        $spelling = trim($spelling);
        $plain = str_replace('*', '', $spelling);
        if ($isMild || $isReligious || in_array($plain, $neverList, true)) {
            $leftOut[$plain] = true;
        } else {
            $alreadyListed[$plain] = true;
            $keptSpellings[] = $spelling;
        }
    }
    if ($keptSpellings !== []) {
        $kept[] = implode('|', $keptSpellings);
    }
}

// --- 2. Google list: plain words not seen yet ---

foreach ($googleLines as $line) {
    $word = strtolower(trim($line));
    $isPlain = preg_match('/^[a-z0-9 -]{3,}$/', $word) === 1;
    if (!$isPlain || isset($leftOut[$word]) || isset($alreadyListed[$word])) {
        continue;
    }
    $kept[] = $word;
    $alreadyListed[$word] = true;
}

// --- 3. Write the list ---

sort($kept);
$header = [
    '# Starter word list for phpChess (B36, A91). Made by tools/build-word-list.php.',
    '# The admin can change it on the community settings page.',
    '#',
    '# Made from dsojevic/profanity-list, MIT licence, Copyright (c) 2021 David Sojevic,',
    '# and coffee-and-fun/google-profanity-words, MIT licence,',
    '# Copyright (c) Robert James Gabriel / Coffee & Fun LLC.',
    '# Permission is hereby granted, free of charge, to any person obtaining a copy of',
    '# this software and associated documentation files (the "Software"), to deal in the',
    '# Software without restriction, including without limitation the rights to use, copy,',
    '# modify, merge, publish, distribute, sublicense, and/or sell copies of the Software,',
    '# and to permit persons to whom the Software is furnished to do so, subject to the',
    '# following conditions: The above copyright notice and this permission notice shall',
    '# be included in all copies or substantial portions of the Software.',
    '# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,',
    '# INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A',
    '# PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT',
    '# HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF',
    '# CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE',
    '# OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.',
];
$target = __DIR__ . '/../src/Domain/Community/starter-words.txt';
file_put_contents($target, implode("\n", array_merge($header, $kept)) . "\n");

echo 'Wrote ' . count($kept) . ' entries (' . count($leftOut) . " spellings left out) to src/Domain/Community/starter-words.txt\n";
