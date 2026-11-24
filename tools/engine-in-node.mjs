/**
 * Loads the computer player's engine files into Node, exactly as the browser
 * worker loads them (development only: tests and calibration).
 *
 * Features AI-01 to AI-05 (FEATURES.md). Node runs the very same JavaScript
 * files that phones run, so what the tests and the calibration measure is what
 * players get (A51). Hosting never needs Node (A1).
 *
 * How: the files are joined and wrapped in one function, so their global
 * variables become fast local ones (a Node "vm" sandbox made GarboChess about
 * 20 times slower). The function returns the names the tests and tools use.
 *
 * Usage:
 *   import { loadEngine } from "./engine-in-node.mjs";
 *   const engine = loadEngine();          // a fresh, separate engine
 *   engine.decideMove(fen, player, "game-1", 0, []);
 */

import { readFileSync } from "node:fs";
import { fileURLToPath } from "node:url";
import { dirname, join } from "node:path";

const projectFolder = join(dirname(fileURLToPath(import.meta.url)), "..");

// The files, in the order the worker loads them (computer-player.worker.js).
const ENGINE_FILES = [
    "public/assets/vendor/garbochess/garbochess.js",
    "public/assets/js/engine/move-chooser.js",
    "public/assets/js/engine/opening-book.js",
    "public/assets/js/engine/garbochess-engine.js",
];

// Functions the tests and tools call.
const FUNCTIONS = [
    // GarboChess
    "ResetGame", "InitializeFromFen", "GetFen", "GenerateValidMoves", "MakeMove", "UnmakeMove",
    "FormatMove", "GetMoveSAN",
    // our files
    "seedFromText", "makeRandom", "randomForMove", "comparableScore", "chooseMove", "settingsForGame",
    "setOpeningBook", "chooseBookMove", "openingName",
    "loadPosition", "depthForPosition", "isCaptureMove", "scoreAllMoves", "listLegalMoves", "decideMove",
];

// GarboChess variables they read (always the current value).
const VARIABLES = ["g_board", "g_inCheck", "g_nodeCount"];

// The code read once; each engine runs it again for its own board.
const engineCode = ENGINE_FILES.map((file) => readFileSync(join(projectFolder, file), "utf8")).join("\n;\n");
const exportCode = "\nreturn {\n"
    + FUNCTIONS.map((name) => "    " + name + ": " + name + ",").join("\n") + "\n"
    + VARIABLES.map((name) => "    get " + name + "() { return " + name + "; },").join("\n") + "\n};\n";

/**
 * Returns a new engine with its own board, so several can run side by side.
 * GarboChess expects a worker's "self" and "postMessage"; it gets harmless ones.
 */
export function loadEngine() {
    const createEngine = new Function("self", "postMessage", "importScripts", engineCode + exportCode);
    return createEngine({}, () => {}, () => {});
}
