/**
 * How strong each rating plays, and how each personality plays (development
 * only: the source of every computer player's engine settings).
 *
 * Feature AI-03, decisions A48–A50 and A53. tools/build-player-settings.mjs
 * reads this table and writes an "engine" block into every player in
 * src/Domain/Computer/computer-players.json. The calibration then shows which
 * rows play too strong or too weak; change the row here, build again, and
 * calibrate again. The listed Elo of a player is never changed (A53).
 *
 * Columns of a strength row:
 *   depth     base search depth in a quiet middlegame (A49);
 *   margin    how many centipawns below the best a move may be and still be
 *             played (wider = weaker, A48);
 *   skip      how often the "no sacrifices for nothing" filter is skipped
 *             (only below 500, A48);
 *   bookPly   how many half-moves the player follows the opening book.
 * These are first guesses, to be settled by the calibration.
 */

// --- Strength by rating (A48, A49) ---

export const STRENGTH_ROWS = [
    { elo: 300, depth: 1, margin: 250, skip: 0.5, bookPly: 2 },
    { elo: 400, depth: 1, margin: 200, skip: 0.25, bookPly: 4 },
    { elo: 500, depth: 1, margin: 150, skip: 0, bookPly: 4 },
    { elo: 600, depth: 1, margin: 100, skip: 0, bookPly: 6 },
    { elo: 700, depth: 2, margin: 120, skip: 0, bookPly: 6 },
    { elo: 800, depth: 2, margin: 80, skip: 0, bookPly: 8 },
    { elo: 950, depth: 2, margin: 50, skip: 0, bookPly: 8 },
    { elo: 1100, depth: 3, margin: 60, skip: 0, bookPly: 10 },
    { elo: 1250, depth: 3, margin: 35, skip: 0, bookPly: 10 },
    { elo: 1400, depth: 4, margin: 40, skip: 0, bookPly: 12 },
    { elo: 1550, depth: 4, margin: 25, skip: 0, bookPly: 12 },
    { elo: 1700, depth: 5, margin: 30, skip: 0, bookPly: 14 },
    { elo: 1850, depth: 5, margin: 15, skip: 0, bookPly: 14 },
    { elo: 2000, depth: 6, margin: 10, skip: 0, bookPly: 16 },
];

// Players at or above this rating are marked "slow on phones" (A49, A53).
export const SLOW_ON_PHONES_FROM_ELO = 1400;

// An Uneven player plays this much above or below its rating in some games (A53).
export const UNEVEN_SWING_ELO = 250;

// --- Personalities (A50, A53) ---
// style: centipawn bonuses that make a kind of move look a little better to
// this player; variety: how freely it picks among book moves (0 to 1).

export const PERSONALITIES = {
    Conservative: { style: { quietBonus: 15, captureBonus: -10 }, variety: 0.3, endgameExtra: 0 },
    Aggressive: { style: { checkBonus: 30, captureBonus: 10 }, variety: 0.6, endgameExtra: 0 },
    Balanced: { style: {}, variety: 0.5, endgameExtra: 0 },
    Uneven: { style: {}, variety: 0.7, endgameExtra: 0 },
    Trader: { style: { captureBonus: 25 }, variety: 0.4, endgameExtra: 1 },
};

// --- Turning a row into engine settings ---

/** The row for a rating: the highest row not above it (or the lowest row). */
export function strengthRowFor(elo) {
    let chosen = STRENGTH_ROWS[0];
    for (const row of STRENGTH_ROWS) {
        if (row.elo <= elo) {
            chosen = row;
        }
    }
    return chosen;
}

/**
 * The engine settings (the "player" object of garbochess-engine.js) for a
 * rating and personality.
 */
export function engineSettingsFor(elo, personalityName) {
    const row = strengthRowFor(elo);
    const personality = PERSONALITIES[personalityName] || PERSONALITIES.Balanced;
    const exchangeExtra = row.elo >= 700 ? 1 : 0;
    const endgameExtra = (row.elo >= 1400 ? 2 : row.elo >= 800 ? 1 : 0) + personality.endgameExtra;
    return {
        depth: {
            baseDepth: row.depth,
            exchangeExtra: exchangeExtra,
            endgameExtra: endgameExtra,
            endgamePieces: 4,
            depthCap: row.depth + exchangeExtra + endgameExtra,
        },
        marginCentipawns: row.margin,
        giveawaySkipChance: row.skip,
        book: { maxPly: row.bookPly, variety: personality.variety },
        style: personality.style,
    };
}
