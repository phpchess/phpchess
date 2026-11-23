<?php

/**
 * Imports phpChess 4.4's English strings as a reference file (UI-06, A26).
 *
 * phpChess 4.4 kept its texts in includes/languages/english.txt, one per
 * line: "IDS_NAME || text ||". This tool turns that file into
 * lang/reference/en-4.4.php, a plain PHP array, so later chunks can reuse
 * 4.4's wording when they write their own entries in lang/en.php.
 * The reference file is never loaded by phpChess itself.
 *
 * Run from the project root:
 *     php tools/import-44-strings.php <path to 4.4 english.txt>
 */

declare(strict_types=1);

// --- Reading the 4.4 file ---

$sourceFile = $argv[1] ?? '';
if (!is_file($sourceFile)) {
    fwrite(STDERR, "Usage: php tools/import-44-strings.php <path to includes/languages/english.txt>\n");
    exit(1);
}

$strings = [];
foreach (file($sourceFile) ?: [] as $line) {
    // A line looks like: IDS_LOGIN_BTN || Log in ||
    if (preg_match('/^\s*(IDS_[A-Z0-9_]+)\s*\|\|\s*(.*?)\s*\|\|\s*$/', $line, $parts) !== 1) {
        continue;
    }
    $text = stripslashes($parts[2]);
    $strings[$parts[1]] = trim(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

// --- Writing the reference file ---

$targetFolder = dirname(__DIR__) . '/lang/reference';
if (!is_dir($targetFolder)) {
    mkdir($targetFolder, 0755, true);
}

$contents = "<?php\n\n"
    . "/**\n"
    . " * phpChess 4.4's English strings, for reference only (UI-06, A26).\n"
    . " *\n"
    . " * Made by tools/import-44-strings.php from 4.4's includes/languages/english.txt.\n"
    . " * Not loaded by phpChess: new entries in lang/en.php reuse this wording where it fits.\n"
    . " */\n\n"
    . "declare(strict_types=1);\n\n"
    . 'return ' . var_export($strings, true) . ";\n";

file_put_contents($targetFolder . '/en-4.4.php', $contents);
echo count($strings) . " strings written to lang/reference/en-4.4.php\n";
