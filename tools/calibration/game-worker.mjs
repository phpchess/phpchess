/**
 * One calibration worker thread: plays the games it is sent and reports each
 * result back (development only).
 *
 * Feature AI-05, decision A51. tools/calibrate.mjs starts one of these per
 * processor core, so many games run at the same time. Each thread has its own
 * engine (its own GarboChess board).
 *
 * Message in:  { white: {name, settings}, black: {name, settings}, gameId }
 * Message out: { white, black, gameId, result, reason, moves, milliseconds }
 */

import { parentPort } from "node:worker_threads";
import { loadEngine } from "../engine-in-node.mjs";
import { playGame } from "./play-game.mjs";
import { readFileSync } from "node:fs";

const engine = loadEngine();
engine.setOpeningBook(JSON.parse(readFileSync(new URL("../../public/assets/engine/book/book.json", import.meta.url), "utf8")));

parentPort.on("message", (job) => {
    const startedAt = Date.now();
    const game = playGame(engine, job.white.settings, job.black.settings, job.gameId);
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
