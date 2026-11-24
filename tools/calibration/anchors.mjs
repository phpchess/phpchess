/**
 * The calibration ladder: fixed reference players the characters are measured
 * against (development only).
 *
 * Feature AI-05, decision A51. The ladder runs from a random mover (fixed at
 * Elo 0, a common convention) up to GarboChess at full strength. Each rung is
 * an ordinary player-settings object, the same kind the characters use, so the
 * calibration plays exactly the code the phones run.
 *
 * guessElo is only a first guess, used to pick each player's nearest anchors
 * before the ladder itself has been measured.
 *
 * Every anchor follows the opening book with full variety for its first 8
 * half-moves; without that, two exact players would repeat the same game.
 */

// --- Shared pieces ---

const VARIED_BOOK = { maxPly: 8, variety: 1 };

/** GarboChess at a fixed base depth, playing its best move (margin 0). */
function fullStrength(depth, guessElo) {
    return {
        name: "garbo-d" + depth,
        guessElo: guessElo,
        settings: {
            depth: { baseDepth: depth, exchangeExtra: 1, endgameExtra: 1, endgamePieces: 4, depthCap: depth + 1 },
            marginCentipawns: 0,
            giveawaySkipChance: 0,
            book: VARIED_BOOK,
            style: {},
        },
    };
}

/** GarboChess at a low depth that also plays moves up to "margin" worse than its best. */
function loose(depth, margin, guessElo) {
    return {
        name: "garbo-d" + depth + "-m" + margin,
        guessElo: guessElo,
        settings: {
            depth: { baseDepth: depth, exchangeExtra: 0, endgameExtra: 0, endgamePieces: 0, depthCap: depth },
            marginCentipawns: margin,
            giveawaySkipChance: 0,
            book: VARIED_BOOK,
            style: {},
        },
    };
}

// --- The ladder, weakest first ---

export const ANCHORS = [
    // Elo 0 by definition: any legal move.
    { name: "random", guessElo: 0, settings: { randomMover: true } },
    // Takes something whenever it can, otherwise any move.
    {
        name: "capture-first",
        guessElo: 150,
        settings: {
            depth: { baseDepth: 1, depthCap: 1 },
            marginCentipawns: 1000000,
            giveawaySkipChance: 1,
            style: { captureBonus: 100000 },
        },
    },
    loose(1, 300, 350),
    loose(1, 100, 600),
    fullStrength(1, 800),
    loose(2, 100, 900),
    fullStrength(2, 1100),
    fullStrength(3, 1300),
    fullStrength(4, 1500),
    fullStrength(5, 1700),
    fullStrength(6, 1900),
    fullStrength(7, 2100),
];

/** The anchor whose Elo is fixed; every other rating is measured from it. */
export const FIXED_ANCHOR = { name: "random", elo: 0 };

/**
 * The pairings to play: each anchor against the next two up the ladder, so
 * every rung is measured against players near its own strength.
 */
export function anchorPairings() {
    const pairings = [];
    for (let index = 0; index < ANCHORS.length; index++) {
        for (let step = 1; step <= 2; step++) {
            if (index + step < ANCHORS.length) {
                pairings.push([ANCHORS[index].name, ANCHORS[index + step].name]);
            }
        }
    }
    return pairings;
}
