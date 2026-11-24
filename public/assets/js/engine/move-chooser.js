/**
 * Move chooser: picks the computer's move from a list of scored moves.
 *
 * Feature AI-02 (FEATURES.md), decisions A48 and A49 (DECISIONS.md).
 *
 * This file knows nothing about any engine. It receives every legal move with
 * its score and decides which one the computer plays, using the character's
 * settings and a seeded random number. Because the random number comes from
 * the game id and the move number, the same position and the same settings
 * give the same move on every device (a phone, a desktop, the calibration run).
 *
 * Loaded with importScripts() in the engine worker and by the Node tests
 * (tests/js/), so it uses plain global functions and no modules.
 *
 * One scored move looks like:
 *   { move: "e2e4", centipawns: 31, mateIn: null,
 *     isCapture: false, givesCheck: false, isGiveaway: false }
 * centipawns are from the computer's point of view (higher is better for it).
 * isGiveaway means: the move hands material over at once, for no gain (A48).
 */

"use strict";

// --- Seeded random numbers ---

/**
 * Turns any text (for example "game 42, ply 17") into a 32-bit whole number.
 * This is the FNV-1a hash: simple, well known and the same everywhere.
 */
function seedFromText(text) {
    let hash = 0x811c9dc5;
    for (let index = 0; index < text.length; index++) {
        hash ^= text.charCodeAt(index);
        hash = Math.imul(hash, 0x01000193) >>> 0;
    }
    return hash;
}

/**
 * Returns a function that gives a new random number between 0 and 1 each time
 * it is called. The same seed always gives the same numbers in the same order
 * (the "mulberry32" generator). Math.random() is never used for move choice.
 */
function makeRandom(seed) {
    let state = seed >>> 0;
    return function nextRandom() {
        state = (state + 0x6d2b79f5) >>> 0;
        let mixed = state;
        mixed = Math.imul(mixed ^ (mixed >>> 15), mixed | 1);
        mixed ^= mixed + Math.imul(mixed ^ (mixed >>> 7), mixed | 61);
        return ((mixed ^ (mixed >>> 14)) >>> 0) / 4294967296;
    };
}

/** The random-number function for one move of one game. */
function randomForMove(gameId, ply) {
    return makeRandom(seedFromText("phpchess game " + gameId + " ply " + ply));
}

// --- Comparing scores ---

// A forced mate counts as this many centipawns (minus the moves it takes),
// so "mate in 2" is better than "mate in 5" and better than any material.
const MATE_SCORE = 100000;

/** One number for a scored move, with mates turned into very large numbers. */
function comparableScore(scoredMove) {
    if (scoredMove.mateIn === null || scoredMove.mateIn === undefined) {
        return scoredMove.centipawns;
    }
    if (scoredMove.mateIn > 0) {
        return MATE_SCORE - scoredMove.mateIn;
    }
    return -MATE_SCORE - scoredMove.mateIn;
}

/**
 * The score as the character sees it: the engine's score plus the character's
 * taste (an aggressive player likes captures and checks a little more).
 */
function scoreWithStyle(scoredMove, style) {
    let score = comparableScore(scoredMove);
    if (scoredMove.isCapture) {
        score += style.captureBonus || 0;
    }
    if (scoredMove.givesCheck) {
        score += style.checkBonus || 0;
    }
    if (!scoredMove.isCapture && !scoredMove.givesCheck) {
        score += style.quietBonus || 0;
    }
    return score;
}

// --- Choosing ---

/**
 * Chooses the computer's move.
 *
 * scoredMoves: every legal move, scored (see the top of this file).
 * settings:    { randomMover, marginCentipawns, giveawaySkipChance, style }
 *   randomMover          true for the wood pusher: any legal move, no checks.
 *   marginCentipawns     how far below the best a move may score and still be
 *                        played; a wide margin makes a weaker player.
 *   giveawaySkipChance   0 to 1: how often the "no sacrifices for nothing"
 *                        filter is skipped (only below 500, A48); 0 = never.
 *   style                { captureBonus, checkBonus, quietBonus } in centipawns.
 * random:      a function from makeRandom().
 *
 * Returns { move, reason, candidates } so the page and the tests can explain
 * the choice.
 */
function chooseMove(scoredMoves, settings, random) {
    if (scoredMoves.length === 0) {
        throw new Error("There is no legal move to choose from.");
    }

    // The wood pusher plays any legal move.
    if (settings.randomMover) {
        const index = Math.floor(random() * scoredMoves.length);
        return { move: scoredMoves[index].move, reason: "random", candidates: scoredMoves.length };
    }

    // 1. Leave out moves that give material away for nothing, unless this
    //    character skips the filter this time. If every move is a giveaway
    //    (rare), all of them stay in.
    const skipFilter = random() < (settings.giveawaySkipChance || 0);
    let allowed = scoredMoves;
    if (!skipFilter) {
        const withoutGiveaways = scoredMoves.filter((scoredMove) => !scoredMove.isGiveaway);
        if (withoutGiveaways.length > 0) {
            allowed = withoutGiveaways;
        }
    }

    // 2. Keep the moves within the margin of the best one, as the character sees them.
    const style = settings.style || {};
    const withStyle = allowed.map((scoredMove) => ({
        move: scoredMove.move,
        score: scoreWithStyle(scoredMove, style),
    }));
    const bestScore = Math.max(...withStyle.map((candidate) => candidate.score));
    const margin = Math.max(settings.marginCentipawns || 0, 0);
    const candidates = withStyle.filter((candidate) => candidate.score >= bestScore - margin);

    // 3. Pick one; better moves get a bigger share. A move at the best score
    //    gets weight margin + 1, a move right at the edge of the margin gets 1.
    let totalWeight = 0;
    for (const candidate of candidates) {
        candidate.weight = margin - (bestScore - candidate.score) + 1;
        totalWeight += candidate.weight;
    }
    let ticket = random() * totalWeight;
    for (const candidate of candidates) {
        ticket -= candidate.weight;
        if (ticket < 0) {
            return { move: candidate.move, reason: skipFilter ? "filter skipped" : "chosen", candidates: candidates.length };
        }
    }
    const last = candidates[candidates.length - 1];
    return { move: last.move, reason: skipFilter ? "filter skipped" : "chosen", candidates: candidates.length };
}
