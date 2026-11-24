/**
 * Builds the computer player's opening book (development only).
 *
 * Feature AI-02, decision A52. Reads the Lichess opening list
 * (public/assets/engine/book/a.tsv to e.tsv, CC0: columns eco, name, pgn),
 * turns every line's moves from "1. e4 c5" into "e2e4 c7c5" with GarboChess,
 * and writes public/assets/engine/book/book.json (format: see
 * public/assets/js/engine/opening-book.js).
 *
 * Run again whenever the TSV files change:
 *   node tools/build-book.mjs
 */

import { readFileSync, writeFileSync } from "node:fs";
import { fileURLToPath } from "node:url";
import { dirname, join } from "node:path";
import { loadEngine } from "./engine-in-node.mjs";

const projectFolder = join(dirname(fileURLToPath(import.meta.url)), "..");
const bookFolder = join(projectFolder, "public/assets/engine/book");
const TSV_FILES = ["a.tsv", "b.tsv", "c.tsv", "d.tsv", "e.tsv"];
const START = "rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1";

const engine = loadEngine();
engine.ResetGame();

// --- Turning one line's moves into "e2e4" form ---

/** "1. e4 c5 2. Nf3" -> ["e4", "c5", "Nf3"] (move numbers dropped). */
function sanMoves(pgn) {
    return pgn.split(/\s+/).filter((word) => word !== "" && !/^\d+\.+$/.test(word));
}

/** Removes check, mate and annotation marks, so "Qxf7#" and "Qxf7" match. */
function plainSan(san) {
    return san.replace(/[+#!?]/g, "");
}

/** Plays a line from the start; returns its moves as "e2e4" text, or null if a move is not legal. */
function lineToMoves(pgn) {
    engine.InitializeFromFen(START);
    const result = [];
    for (const san of sanMoves(pgn)) {
        const legal = engine.GenerateValidMoves();
        const match = legal.find((move) => plainSan(engine.GetMoveSAN(move, legal)) === plainSan(san));
        if (match === undefined) {
            return null;
        }
        result.push(engine.FormatMove(match));
        engine.MakeMove(match);
    }
    return result;
}

// --- Building the book ---

const lines = {};
const names = {};
let lineCount = 0;
const problems = [];

for (const file of TSV_FILES) {
    const rows = readFileSync(join(bookFolder, file), "utf8").split(/\r?\n/).slice(1);
    for (const row of rows) {
        if (row.trim() === "") {
            continue;
        }
        const [eco, name, pgn] = row.split("\t");
        const moves = lineToMoves(pgn);
        if (moves === null) {
            problems.push(file + ": " + eco + " " + name);
            continue;
        }
        lineCount++;
        names[moves.join(" ")] = name;

        // Count, for each point of the line, which move comes next.
        for (let index = 0; index < moves.length; index++) {
            const key = moves.slice(0, index).join(" ");
            const next = moves[index];
            lines[key] = lines[key] || [];
            const entry = lines[key].find((existing) => existing[0] === next);
            if (entry) {
                entry[1]++;
            } else {
                lines[key].push([next, 1]);
            }
        }
    }
}

writeFileSync(join(bookFolder, "book.json"), JSON.stringify({ lines: lines, names: names }));
console.log("Book written: " + lineCount + " lines, " + Object.keys(lines).length + " positions.");
if (problems.length > 0) {
    console.log("Lines skipped (a move was not legal):\n  " + problems.join("\n  "));
}
