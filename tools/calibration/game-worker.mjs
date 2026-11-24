/**
 * One calibration worker thread: plays the games it is sent and reports each
 * result back (development only).
 *
 * Feature AI-05, decision A51. tools/calibrate.mjs starts one of these per
 * processor core, so many games run at the same time. Each thread has its own
 * engine (its own GarboChess board).
 *
 * Message in:  { white: side, black: side, gameId }, where a side is an anchor
 *              { name, settings } or a computer player { name, engineBlock }
 *              (its settings for this game come from settingsForGame, A53).
 * Message out: { white, black, gameId, result, reason, moves, milliseconds }
 */

import { parentPort } from "node:worker_threads";
import { loadEngine } from "../engine-in-node.mjs";
import { playGame } from "./play-game.mjs";
import { readFileSync } from "node:fs";

const engine = loadEngine();
engine.setOpeningBook(JSON.parse(readFileSync(new URL("../../public/assets/engine/book/book.json", import.meta.url), "utf8")));

/** The settings one side plays this game with. */
function settingsOf(side, gameId) {
    return side.engineBlock ? engine.settingsForGame(side.engineBlock, gameId) : side.settings;
}

parentPort.on("message", (job) => {
    const startedAt = Date.now();
    const game = playGame(engine, settingsOf(job.white, job.gameId), settingsOf(job.black, job.gameId), job.gameId);
    parentPort.postMessage({
        white: job.white.name,
        black: job.black.name,
        gameId: job.gameId,
        result: game.result,
        reason: game.reason,
        moves: game.moves,
        milliseconds: Date.now() - startedAt,
    });
});
