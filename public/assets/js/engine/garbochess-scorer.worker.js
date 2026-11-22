/**
 * Candidate-move scorer: a Web Worker that scores every legal move of a position.
 *
 * Feature AI-01, AI-04 (FEATURES.md). The computer opponent's selection layer
 * (AI-02) needs an honest score for each candidate move, so it can choose a
 * plausible weaker move instead of the best one without ever blundering at
 * random. GarboChess itself only finds the single best move, so this file
 * searches each legal move separately to the same fixed depth.
 *
 * The depth, not the time, sets the budget: the same position at the same
 * depth gives the same scores on a fast desktop and a slow phone (AI-04).
 *
 * Uses GarboChess (public/assets/vendor/garbochess/, BSD-3-Clause, unchanged)
 * through its own functions: ResetGame, InitializeFromFen, GenerateValidMoves,
 * MakeMove, UnmakeMove, AlphaBeta, FormatMove.
 *
 * Messages in:   { type: "score", fen: "<FEN>", depth: <whole number, 1 or more> }
 * Messages out:  { type: "scores", fen, depth, milliseconds, nodes,
 *                  moves: [ { move: "e2e4", centipawns: 31, mateIn: null }, ... ] }
 *                (best move first, scores from the side to move's point of view)
 *                { type: "error", message: "<text>" }
 */

"use strict";

// --- Loading GarboChess ---

// GarboChess sets its own onmessage handler when it loads; we replace it below,
// so only this file's protocol is active.
importScripts("../../vendor/garbochess/garbochess.js");

// GarboChess counts a pawn as 800; dividing by 8 gives centipawns.
const GARBOCHESS_UNITS_PER_CENTIPAWN = 8;

// Scores this close to GarboChess's maximum mean a forced mate.
const MATE_THRESHOLD = maxEval - 1000;

// --- Scoring one position ---

/**
 * Scores every legal move in the position to the given depth.
 * Returns the moves sorted best first.
 */
function scoreAllMoves(fen, depth) {
    ResetGame();
    const problem = InitializeFromFen(fen);
    if (problem.length !== 0) {
        throw new Error("Invalid position: " + problem);
    }

    // GarboChess stops a search when its time is up; the budget here is the
    // depth, so give it unlimited time and mark the search as valid.
    g_timeout = Number.MAX_SAFE_INTEGER;
    g_searchValid = true;
    g_startTime = Date.now();
    g_nodeCount = 0;
    g_globalPly++;

    const scoredMoves = [];
    for (const move of GenerateValidMoves()) {
        scoredMoves.push(scoreOneMove(move, depth));
    }

    scoredMoves.sort((first, second) => second.rawScore - first.rawScore);
    return scoredMoves.map(toReadableScore);
}

/**
 * Plays the move, searches the reply to depth - 1 with iterative deepening
 * (which fills GarboChess's hash table and makes the deeper search faster),
 * then takes the move back. The score is negated because the search answers
 * from the opponent's point of view.
 */
function scoreOneMove(move, depth) {
    MakeMove(move);

    let replyScore = 0;
    const replyDepth = Math.max(depth - 1, 0);
    for (let currentDepth = 0; currentDepth <= replyDepth; currentDepth++) {
        replyScore = AlphaBeta(currentDepth, 1, minEval, maxEval);
    }

    UnmakeMove(move);

    return { move: move, rawScore: -replyScore };
}

// --- Turning GarboChess's numbers into readable ones ---

/**
 * Converts a raw GarboChess score into centipawns, or into "mate in N moves"
 * when the score means a forced mate.
 */
function toReadableScore(scoredMove) {
    const raw = scoredMove.rawScore;
    let mateIn = null;
    let centipawns = Math.round(raw / GARBOCHESS_UNITS_PER_CENTIPAWN);

    if (raw > MATE_THRESHOLD) {
        mateIn = Math.ceil((maxEval - raw) / 2);
    } else if (raw < -MATE_THRESHOLD) {
        mateIn = -Math.ceil((maxEval + raw) / 2);
    }

    return { move: FormatMove(scoredMove.move), centipawns: centipawns, mateIn: mateIn };
}

// --- Talking to the page ---

self.onmessage = function (event) {
    const request = event.data;

    if (request === null || typeof request !== "object" || request.type !== "score") {
        self.postMessage({ type: "error", message: "Unknown request." });
        return;
    }
    if (!Number.isInteger(request.depth) || request.depth < 1) {
        self.postMessage({ type: "error", message: "Depth must be a whole number of 1 or more." });
        return;
    }

    try {
        const startedAt = performance.now();
        const moves = scoreAllMoves(String(request.fen), request.depth);
        self.postMessage({
            type: "scores",
            fen: request.fen,
            depth: request.depth,
            milliseconds: Math.round(performance.now() - startedAt),
            nodes: g_nodeCount,
            moves: moves,
        });
    } catch (problem) {
        self.postMessage({ type: "error", message: String(problem.message || problem) });
    }
};
