/**
 * GarboChess helpers: score every legal move, decide the search depth for a
 * position, and find moves that give material away for nothing.
 *
 * Features AI-01, AI-02, AI-04 (FEATURES.md), decisions A47–A49 (DECISIONS.md).
 *
 * GarboChess (public/assets/vendor/garbochess/, BSD-3-Clause, unchanged) only
 * finds one best move. The computer opponent needs a score for every move, so
 * this file searches each legal move on its own to the same depth.
 *
 * The budget is always a depth, never a time: the same position and settings
 * give the same scores on a fast desktop and a slow phone (AI-04). The depth
 * itself depends on the position (A49): more in exchanges and endgames.
 *
 * Loaded after garbochess.js with importScripts() in the engine worker and by
 * the Node tests and calibration (tests/js/, tools/calibrate.mjs). It uses
 * GarboChess's own global functions and variables: ResetGame,
 * InitializeFromFen, GetFen, GenerateValidMoves, MakeMove, UnmakeMove,
 * AlphaBeta, QSearch, See, FormatMove, g_board, g_inCheck, g_nodeCount.
 */

"use strict";

// --- GarboChess's numbers ---

// GarboChess counts a pawn as 800; dividing by 8 gives centipawns.
const GARBOCHESS_UNITS_PER_CENTIPAWN = 8;

// Scores this close to GarboChess's maximum mean a forced mate.
const MATE_THRESHOLD = maxEval - 1000;

// A move "gives material away" (A48) when it loses at least this much at once
// and the deeper check finds no gain making up for it (1.5 pawns).
const GIVEAWAY_CENTIPAWNS = 150;

// The giveaway check always looks at least this deep, even for a player whose
// own depth is lower, so that a real sacrifice is not mistaken for a giveaway.
const GIVEAWAY_CONFIRM_DEPTH = 3;

// GarboChess piece codes (low 3 bits of a board square).
const PIECE_PAWN = 1;
const PIECE_KING = 6;

// --- Setting up a position ---

/** Loads a position into GarboChess, or throws if the FEN is not valid. */
function loadPosition(fen) {
    const problem = InitializeFromFen(fen);
    if (problem.length !== 0) {
        throw new Error("Invalid position: " + problem);
    }
}

/**
 * Prepares GarboChess for a search limited by depth only: empty hash table,
 * unlimited time. Called once per request, so every request starts the same
 * way and gives the same result.
 */
function startFreshSearch() {
    ResetGame();
    g_timeout = Number.MAX_SAFE_INTEGER;
    g_searchValid = true;
    g_startTime = Date.now();
    g_nodeCount = 0;
    g_globalPly++;
}

// --- Choosing the depth for a position (A49) ---

/**
 * How many pieces other than kings and pawns are on the board, both sides.
 * Few pieces means an endgame.
 */
function countOfficers() {
    let count = 0;
    for (let square = 0; square < 256; square++) {
        const piece = g_board[square] & 0x7;
        if (piece !== 0 && piece !== PIECE_PAWN && piece !== PIECE_KING && g_board[square] !== 0x80) {
            count++;
        }
    }
    return count;
}

/** True when the side to move is in check or has a capture that does not lose material. */
function hasExchangeGoingOn(legalMoves) {
    if (g_inCheck) {
        return true;
    }
    return legalMoves.some((move) => isCaptureMove(move) && See(move));
}

/**
 * The search depth for this position, from the player's depth settings:
 *   baseDepth       depth in a quiet middlegame position;
 *   exchangeExtra   added while captures or checks are going on;
 *   endgameExtra    added when at most endgamePieces officers are left;
 *   depthCap        the highest depth allowed (the admin's cap, A49).
 * Depends only on the position, so it is the same on every device.
 */
function depthForPosition(fen, depthSettings) {
    startFreshSearch();
    loadPosition(fen);
    const legalMoves = GenerateValidMoves();

    let depth = depthSettings.baseDepth;
    if (hasExchangeGoingOn(legalMoves)) {
        depth += depthSettings.exchangeExtra || 0;
    }
    if (countOfficers() <= (depthSettings.endgamePieces || 0)) {
        depth += depthSettings.endgameExtra || 0;
    }
    const cap = depthSettings.depthCap || depth;
    return Math.max(1, Math.min(depth, cap));
}

// --- Facts about one move ---

/** True when the move takes a piece (including en passant). */
function isCaptureMove(move) {
    const target = (move >> 8) & 0xFF;
    return g_board[target] !== 0 || (move & moveflagEPC) !== 0;
}

/**
 * Searches the position after the move and returns its raw GarboChess score
 * from the moving side's point of view. Iterative deepening fills the hash
 * table first, which makes the deeper search faster.
 */
function searchAfterMove(move, depth) {
    MakeMove(move);
    let replyScore = 0;
    for (let currentDepth = 0; currentDepth <= depth - 1; currentDepth++) {
        replyScore = AlphaBeta(currentDepth, 1, minEval, maxEval);
    }
    UnmakeMove(move);
    return -replyScore;
}

/**
 * The quick "what happens to the material now" value after the move: only
 * captures are followed (GarboChess's quiescence search). From the moving
 * side's point of view, in raw GarboChess units.
 */
function quickValueAfterMove(move) {
    MakeMove(move);
    const value = -QSearch(minEval, maxEval, 0);
    UnmakeMove(move);
    return value;
}

/**
 * The quick value if the side to move could "pass": what the opponent could
 * already win right now. A move is only a giveaway if it makes things worse
 * than that, so missing an existing threat is not counted (that is a missed
 * tactic, which A48 allows), but putting a piece en prise is.
 * Returns null when passing is impossible (the side to move is in check).
 */
function quickValueIfPassing(fen) {
    if (g_inCheck) {
        return null;
    }
    const fields = fen.trim().split(/\s+/);
    fields[1] = fields[1] === "w" ? "b" : "w";
    fields[3] = "-";
    loadPosition(fields.join(" "));
    const value = -QSearch(minEval, maxEval, 0);
    loadPosition(fen);
    return value;
}

// --- Scoring every move ---

/**
 * Scores every legal move of the position at the given depth and marks the
 * captures, checks and giveaways. Returns the moves sorted best first, each as
 * { move, centipawns, mateIn, isCapture, givesCheck, isGiveaway }
 * (the format move-chooser.js expects).
 */
function scoreAllMoves(fen, depth) {
    startFreshSearch();
    loadPosition(fen);

    // Facts that need the position before any move is made.
    const legalMoves = GenerateValidMoves();
    const facts = legalMoves.map((move) => {
        const isCapture = isCaptureMove(move);
        MakeMove(move);
        const givesCheck = g_inCheck;
        UnmakeMove(move);
        return { move: move, isCapture: isCapture, givesCheck: givesCheck, quickValue: quickValueAfterMove(move) };
    });
    let passValue = quickValueIfPassing(fen);
    if (passValue === null) {
        // In check there is no "pass": compare with the best quick value instead.
        passValue = Math.max(...facts.map((fact) => fact.quickValue));
    }

    // The real scores, at the player's depth.
    for (const fact of facts) {
        fact.rawScore = searchAfterMove(fact.move, depth);
    }
    facts.sort((first, second) => second.rawScore - first.rawScore);

    markGiveaways(facts, passValue, depth);
    return facts.map(toScoredMove);
}

/**
 * Marks the moves that give material away for nothing (A48): the move loses at
 * least 1.5 pawns at once compared with passing, AND a search at least
 * GIVEAWAY_CONFIRM_DEPTH deep finds it at least 1.5 pawns worse than the best
 * move, so no attack or combination makes up for it.
 */
function markGiveaways(facts, passValue, depth) {
    const limit = GIVEAWAY_CENTIPAWNS * GARBOCHESS_UNITS_PER_CENTIPAWN;
    const confirmDepth = Math.max(depth, GIVEAWAY_CONFIRM_DEPTH);

    // The best move's score at the confirming depth (the same as its normal
    // score when the player already searches that deep).
    const bestConfirmed = depth >= confirmDepth ? facts[0].rawScore : searchAfterMove(facts[0].move, confirmDepth);

    for (const fact of facts) {
        fact.isGiveaway = false;
        if (passValue - fact.quickValue < limit) {
            continue;
        }
        const confirmed = depth >= confirmDepth ? fact.rawScore : searchAfterMove(fact.move, confirmDepth);
        fact.isGiveaway = bestConfirmed - confirmed >= limit;
    }
}

/** Converts GarboChess's raw score into centipawns or "mate in N moves". */
function toScoredMove(fact) {
    const raw = fact.rawScore;
    let mateIn = null;
    if (raw > MATE_THRESHOLD) {
        mateIn = Math.ceil((maxEval - raw) / 2);
    } else if (raw < -MATE_THRESHOLD) {
        mateIn = -Math.ceil((maxEval + raw) / 2);
    }
    return {
        move: FormatMove(fact.move),
        centipawns: Math.round(raw / GARBOCHESS_UNITS_PER_CENTIPAWN),
        mateIn: mateIn,
        isCapture: fact.isCapture,
        givesCheck: fact.givesCheck,
        isGiveaway: fact.isGiveaway,
    };
}

/** All legal moves of a position as text ("e2e4"), for the wood pusher. */
function listLegalMoves(fen) {
    loadPosition(fen);
    return GenerateValidMoves().map((move) => ({
        move: FormatMove(move), centipawns: 0, mateIn: null,
        isCapture: false, givesCheck: false, isGiveaway: false,
    }));
}

// --- One full decision ---

/**
 * Decides the computer's move in one position.
 * player: { randomMover, depth: {...}, marginCentipawns, giveawaySkipChance, style }
 * gameId, ply: seed the random choice (same move on every device).
 * Returns { move, depth, reason, candidates, nodes, scores }.
 */
function decideMove(fen, player, gameId, ply) {
    const random = randomForMove(gameId, ply);
    if (player.randomMover) {
        const choice = chooseMove(listLegalMoves(fen), player, random);
        return { move: choice.move, depth: 0, reason: choice.reason, candidates: choice.candidates, nodes: 0, scores: [] };
    }

    const depth = depthForPosition(fen, player.depth);
    const scores = scoreAllMoves(fen, depth);
    const choice = chooseMove(scores, player, random);
    return { move: choice.move, depth: depth, reason: choice.reason, candidates: choice.candidates, nodes: g_nodeCount, scores: scores };
}
