/**
 * Computer player: a Web Worker that decides the computer opponent's move.
 *
 * Features AI-01 to AI-04 (FEATURES.md), decisions A47–A49 (DECISIONS.md).
 *
 * Runs in the player's browser so the server does no chess thinking. The move
 * it decides is then sent to the server like any other move, and the server
 * checks it (K1). The same position, player settings, game and move number
 * always give the same move, on every device.
 *
 * Files loaded, in order:
 *   GarboChess (vendor, BSD-3-Clause, unchanged), then our move chooser, opening book and
 *   our GarboChess helpers. To use a different engine, see docs/ENGINE_HOWTO.md.
 *
 * Messages in:
 *   { type: "decide", fen, player: {...}, gameId, ply, movesSoFar }   (movesSoFar: null if not from the normal start)
 *   { type: "score",  fen, depth }            (every move scored, for tests and the admin page)
 * Messages out:
 *   { type: "decision", fen, move, depth, reason, candidates, nodes, milliseconds }
 *   { type: "scores",   fen, depth, moves: [...], nodes, milliseconds }
 *   { type: "error",    message }
 */

"use strict";

// --- Loading the engine ---

// GarboChess sets its own onmessage handler when it loads; ours below replaces
// it, so only this file's protocol is active.
importScripts(
    "../../vendor/garbochess/garbochess.js",
    "move-chooser.js",
    "opening-book.js",
    "garbochess-engine.js"
);

// The opening book (A52), read once from the install itself the first time a
// move is decided. A missing book only means no book moves.
let bookLoaded = false;
function loadBookOnce() {
    if (bookLoaded) {
        return;
    }
    bookLoaded = true;
    try {
        const request = new XMLHttpRequest();
        request.open("GET", "../../engine/book/book.json", false); // a worker may wait
        request.send();
        if (request.status === 200) {
            setOpeningBook(JSON.parse(request.responseText));
        }
    } catch (problem) {
        setOpeningBook(null);
    }
}

// --- Talking to the page ---

self.onmessage = function (event) {
    const request = event.data;
    const startedAt = performance.now();

    try {
        if (request === null || typeof request !== "object") {
            throw new Error("Unknown request.");
        }

        if (request.type === "decide") {
            loadBookOnce();
            const movesSoFar = Array.isArray(request.movesSoFar) ? request.movesSoFar.map(String) : null;
            const decision = decideMove(String(request.fen), request.player, String(request.gameId), Number(request.ply), movesSoFar);
            self.postMessage({
                type: "decision",
                fen: request.fen,
                move: decision.move,
                depth: decision.depth,
                reason: decision.reason,
                candidates: decision.candidates,
                nodes: decision.nodes,
                milliseconds: Math.round(performance.now() - startedAt),
            });
            return;
        }

        if (request.type === "score") {
            if (!Number.isInteger(request.depth) || request.depth < 1) {
                throw new Error("Depth must be a whole number of 1 or more.");
            }
            const moves = scoreAllMoves(String(request.fen), request.depth);
            self.postMessage({
                type: "scores",
                fen: request.fen,
                depth: request.depth,
                moves: moves,
                nodes: g_nodeCount,
                milliseconds: Math.round(performance.now() - startedAt),
            });
            return;
        }

        throw new Error("Unknown request.");
    } catch (problem) {
        self.postMessage({ type: "error", message: String(problem.message || problem) });
    }
};
