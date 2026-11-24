/**
 * Loads the computer player's engine files into Node, exactly as the browser
 * worker loads them (development only: tests and calibration).
 *
 * Features AI-01 to AI-05 (FEATURES.md). Node runs the very same JavaScript
 * files that phones run, so what the tests and the calibration measure is what
 * players get (A51). Hosting never needs Node (A1).
 *
 * Usage:
 *   import { loadEngine } from "./engine-in-node.mjs";
 *   const engine = loadEngine();          // a fresh, separate engine
 *   engine.decideMove(fen, player, "game-1", 0);
 */

import { readFileSync } from "node:fs";
import { fileURLToPath } from "node:url";
import { dirname, join } from "node:path";
import vm from "node:vm";

const projectFolder = join(dirname(fileURLToPath(import.meta.url)), "..");

// The files, in the order the worker loads them (computer-player.worker.js).
const ENGINE_FILES = [
    "public/assets/vendor/garbochess/garbochess.js",
    "public/assets/js/engine/move-chooser.js",
    "public/assets/js/engine/garbochess-engine.js",
];

/**
 * Returns a new engine: a separate sandbox holding GarboChess and our files.
 * Each sandbox has its own board, so several can run side by side.
 */
export function loadEngine() {
    // GarboChess expects a worker's "self" and "postMessage"; give it harmless ones.
    const sandbox = { postMessage() {}, Date, Math, performance };
    sandbox.self = sandbox;
    vm.createContext(sandbox);

    // The files declare their functions with "function" and "var", which
    // become properties of the sandbox. Their "const" values stay inside, so
    // the whole set of files is run as one script.
    const code = ENGINE_FILES.map((file) => readFileSync(join(projectFolder, file), "utf8")).join("\n;\n");
    vm.runInContext(code, sandbox, { filename: "engine-files.js" });
    return sandbox;
}
