/**
 * Move chooser: picks the computer's move from a list of scored moves.
 *
 * Feature AI-02 (FEATURES.md), decisions A48, A49, A56 and A57 (DECISIONS.md).
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

// --- Human-like play (A56, A57) ---

// A tactic must win at least this much more than the best quiet move to be
// one the player can overlook (a blind spot).
const BLIND_SPOT_GAIN_CENTIPAWNS = 150;

/**
 * True when the move is a tactic a weak player may not see (A57): a mate, or
 * a capture or check that wins clearly more than the best quiet move.
 * Taking back a piece that was just taken (isRecapture) is never hidden:
 * people of every rating recapture.
 */
function isTactic(scoredMove, bestQuietScore) {
    if (scoredMove.isRecapture) {
        return false;
    }
    if (scoredMove.mateIn !== null && scoredMove.mateIn !== undefined && scoredMove.mateIn > 0) {
        return true;
    }
    const forcing = scoredMove.isCapture || scoredMove.givesCheck;
    return forcing && comparableScore(scoredMove) >= bestQuietScore + BLIND_SPOT_GAIN_CENTIPAWNS;
}

/**
 * A random amount between -noise and +noise, more often near 0 than near the
 * ends (the sum of two even draws, a "triangle"). Added to each move's value,
 * it blurs the player's judgement: small positional differences disappear,
 * large material differences stay (A57).
 */
function blur(noise, random) {
    return noise * (random() + random() - 1);
}

// --- Choosing ---

/**
 * Chooses the computer's move.
 *
 * scoredMoves: every legal move, scored (see the top of this file); a move may
 *              also carry isRecapture (it takes back on the square where the
 *              opponent just captured).
 * settings:    { randomMover, giveawayChance, blindSpotChance, noiseCentipawns,
 *                marginCentipawns, giveawaySkipChance, style }
 *   randomMover          true for the wood pusher: any legal move, no checks.
 *   giveawayChance       0 to 1: how often the player gives material away for
 *                        nothing, as people of its rating do (A56).
 *   blindSpotChance      0 to 1: how often the player does not see the
 *                        tactics in the position (mates, winning captures and
 *                        checks; never a recapture) (A57).
 *   noiseCentipawns      how blurred the player's judgement is: each move's
 *                        value moves by up to this much, at random (A57).
 *   marginCentipawns     how far below the best (blurred) value a move may be
 *                        and still be played; 0 = always the best as seen.
 *                        Used by the calibration anchors; players use noise.
 *   giveawaySkipChance   0 to 1: how often the "no sacrifices for nothing"
 *                        filter is skipped; 0 = never (the full-strength
 *                        calibration anchors use 1: no filter at all).
 *   style                { captureBonus, checkBonus, quietBonus } in centipawns.
 * random:      a function from makeRandom().
 *
 * Returns { move, reason, candidates } so the page and the tests can explain
 * the choice. Reasons: "random", "giveaway", "blind spot", "filter skipped",
 * "chosen".
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

    // 1. Sometimes a sacrifice for nothing, as people of this rating make (A56).
    const giveaways = scoredMoves.filter((scoredMove) => scoredMove.isGiveaway);
    if (random() < (settings.giveawayChance || 0) && giveaways.length > 0) {
        const picked = giveaways[Math.floor(random() * giveaways.length)];
        return { move: picked.move, reason: "giveaway", candidates: giveaways.length };
    }

    // 2. Otherwise leave out the moves that give material away for nothing,
    //    unless this character skips the filter this time. If every move is a
    //    giveaway (rare), all of them stay in.
    const skipFilter = random() < (settings.giveawaySkipChance || 0);
    let allowed = scoredMoves;
    if (!skipFilter) {
        const withoutGiveaways = scoredMoves.filter((scoredMove) => !scoredMove.isGiveaway);
        if (withoutGiveaways.length > 0) {
            allowed = withoutGiveaways;
        }
    }

    // 3. A blind spot (A57): this time the player does not see the tactics.
    //    If only tactics are left (rare), it sees them after all.
    let reason = skipFilter ? "filter skipped" : "chosen";
    if (random() < (settings.blindSpotChance || 0)) {
        const quietScores = allowed
            .filter((scoredMove) => !scoredMove.isCapture && !scoredMove.givesCheck)
            .map(comparableScore);
        if (quietScores.length > 0) {
            const bestQuietScore = Math.max(...quietScores);
            const unseen = allowed.filter((scoredMove) => !isTactic(scoredMove, bestQuietScore));
            if (unseen.length > 0 && unseen.length < allowed.length) {
                allowed = unseen;
                reason = "blind spot";
            }
        }
    }

    // 4. The value of each move as this character sees it: the engine's
    //    score, its taste (style), and its blurred judgement (noise).
    const style = settings.style || {};
    const noise = Math.max(settings.noiseCentipawns || 0, 0);
    const seen = allowed.map((scoredMove) => ({
        move: scoredMove.move,
        score: scoreWithStyle(scoredMove, style) + (noise > 0 ? blur(noise, random) : 0),
    }));

    // 5. Keep the moves within the margin of the best one as seen, and pick
    //    one; better moves get a bigger share. A move at the best value gets
    //    weight margin + 1, a move right at the edge of the margin gets 1.
    const bestScore = Math.max(...seen.map((candidate) => candidate.score));
    const margin = Math.max(settings.marginCentipawns || 0, 0);
    const candidates = seen.filter((candidate) => candidate.score >= bestScore - margin);
    let totalWeight = 0;
    for (const candidate of candidates) {
        candidate.weight = margin - (bestScore - candidate.score) + 1;
        totalWeight += candidate.weight;
    }
    let ticket = random() * totalWeight;
    for (const candidate of candidates) {
        ticket -= candidate.weight;
        if (ticket < 0) {
            return { move: candidate.move, reason: reason, candidates: candidates.length };
        }
    }
    const last = candidates[candidates.length - 1];
    return { move: last.move, reason: reason, candidates: candidates.length };
}

// --- The settings for one game (A53) ---

/**
 * Picks a computer player's settings for one game from its "engine" block in
 * computer-players.json. Most players always use "normal". An Uneven player
 * plays some games at its "low" and some at its "high" settings: a third of
 * its games each, chosen from the game id, so every device agrees.
 */
function settingsForGame(engineBlock, gameId) {
    if (!engineBlock.low || !engineBlock.high) {
        return engineBlock.normal;
    }
    const roll = makeRandom(seedFromText("phpchess game " + gameId + " form"))();
    if (roll < 1 / 3) {
        return engineBlock.low;
    }
    if (roll < 2 / 3) {
        return engineBlock.high;
    }
    return engineBlock.normal;
}
