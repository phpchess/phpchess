/**
 * How strong each rating plays, and how each personality plays (development
 * only: the source of every computer player's engine settings).
 *
 * Feature AI-03, decisions A48–A50, A53, A56, A57 and A58. tools/build-player-settings.mjs
 * reads this table and writes an "engine" block into every player in
 * src/Domain/Computer/computer-players.json. The calibration then shows which
 * rows play too strong or too weak; change the row here, build again, and
 * calibrate again. The listed Elo of a player is never changed (A53).
 *
 * Columns of a strength row:
 *   depth      base search depth in a quiet middlegame (A49); no player ever
 *              searches deeper than MAX_DEPTH (A55);
 *   noise      how blurred the player's judgement is, in centipawns: each
 *              move's value moves by up to this much at random. Small
 *              positional differences disappear, material stays (A57);
 *   blindSpot  per cent of moves in which the player does not see the
 *              tactics (mates, winning captures and checks; never a
 *              recapture) (A57);
 *   threatBlind per cent of moves in which the player does not look at the
 *              opponent's reply at all: attacked pieces are left standing,
 *              threatened mates and forks go unseen (A58);
 *   giveaway   per cent of moves in which the player gives material away
 *              for nothing, as people of its rating do (A56);
 *   bookPly    how many half-moves the player follows the opening book.
 *
 * Where the numbers come from (2026-11-25):
 *   giveaway   the 2026-11-24 tuning run (600 games) measured our giveaways
 *              against people's (Lichess profile, A55) and corrected them;
 *              giveaways matched well, so these are the corrected values;
 *   noise      starts near the old margins (the same kind of looseness), a
 *              little wider from 700 up, where our players lost only about
 *              half as much per move as people;
 *   blindSpot  first estimate: half of the moves at 300, falling to 1 % at
 *              2000.
 *   2026-11-25 after the 120-game indicative run (our players lost only 1/4 to
 *   2/3 as much per move as people): more noise, one depth less from 800 up,
 *   and threat blindness added (A58). After the second indicative run, threat
 *   blindness doubled from 1250 up (those rows still lost half as much as people).
 *   Final adjustment after the 600-game run (A56: one adjustment, no further
 *   run; the check run is backlog B32): from 700 up noise x 1.4 and threat
 *   blindness x 1.3 (those rows lost 45-80 % as much as people); from 950 up
 *   the giveaway chance lowered by our excess over people (threat blindness
 *   adds giveaways of its own).
 *   Noise and blind spots are checked against people's average loss per
 *   move by tools/human-profile.mjs; the indicative run comes first (A57).
 */

// --- Strength by rating (A48, A49, A56, A57) ---

export const STRENGTH_ROWS = [
    { elo: 300, depth: 1, noise: 300, threatBlind: 30, blindSpot: 50, giveaway: 12.3, bookPly: 2 },
    { elo: 400, depth: 1, noise: 280, threatBlind: 27, blindSpot: 45, giveaway: 11.6, bookPly: 4 },
    { elo: 500, depth: 1, noise: 260, threatBlind: 24, blindSpot: 40, giveaway: 11.2, bookPly: 4 },
    { elo: 600, depth: 1, noise: 240, threatBlind: 21, blindSpot: 35, giveaway: 8.5, bookPly: 6 },
    { elo: 700, depth: 1, noise: 310, threatBlind: 23, blindSpot: 30, giveaway: 7.5, bookPly: 6 },
    { elo: 800, depth: 1, noise: 280, threatBlind: 20, blindSpot: 25, giveaway: 5.0, bookPly: 8 },
    { elo: 950, depth: 1, noise: 250, threatBlind: 16, blindSpot: 20, giveaway: 2.6, bookPly: 8 },
    { elo: 1100, depth: 2, noise: 225, threatBlind: 13, blindSpot: 15, giveaway: 2.3, bookPly: 10 },
    { elo: 1250, depth: 2, noise: 195, threatBlind: 21, blindSpot: 10, giveaway: 0.6, bookPly: 10 },
    { elo: 1400, depth: 2, noise: 170, threatBlind: 16, blindSpot: 7, giveaway: 0.9, bookPly: 12 },
    { elo: 1550, depth: 3, noise: 140, threatBlind: 13, blindSpot: 5, giveaway: 0.5, bookPly: 12 },
    { elo: 1700, depth: 3, noise: 120, threatBlind: 10, blindSpot: 3, giveaway: 0.1, bookPly: 14 },
    { elo: 1850, depth: 3, noise: 100, threatBlind: 8, blindSpot: 2, giveaway: 0.4, bookPly: 14 },
    { elo: 2000, depth: 3, noise: 85, threatBlind: 6.5, blindSpot: 1, giveaway: 0.3, bookPly: 16 },
];

// No player searches deeper than this, extensions included (A55).
export const MAX_DEPTH = 5;

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
            depthCap: Math.min(MAX_DEPTH, row.depth + exchangeExtra + endgameExtra),
        },
        noiseCentipawns: row.noise,
        marginCentipawns: 0,
        giveawaySkipChance: 0,
        blindSpotChance: row.blindSpot / 100,
        threatBlindChance: row.threatBlind / 100,
        giveawayChance: row.giveaway / 100,
        book: { maxPly: row.bookPly, variety: personality.variety },
        style: personality.style,
    };
}
