/**
 * Elo ratings from game results, with a 95 % confidence range (development
 * only: calibration).
 *
 * Feature AI-05, decision A51. A maximum-likelihood fit in the spirit of
 * BayesElo: it finds the ratings that make the actual results most likely,
 * using the Elo formula  expected score = 1 / (1 + 10^((rb - ra) / 400)).
 * A draw counts as half a win and half a loss. One player's rating is fixed
 * (the anchor); all others are measured from it.
 *
 * Kept simple on purpose (A21): a plain step-by-step improvement until the
 * ratings stop changing, then the confidence range from how sharply the
 * likelihood falls away around each rating.
 */

// --- The Elo formula ---

/** Expected score of a player rated ra against one rated rb. */
export function expectedScore(ra, rb) {
    return 1 / (1 + Math.pow(10, (rb - ra) / 400));
}

// --- The fit ---

/**
 * games: [{ white, black, score }] where score is White's result: 1, 0.5 or 0.
 * fixed: { name, elo } the anchor.
 * Returns { name: { elo, low, high, games } } with the 95 % range [low, high].
 */
export function fitRatings(games, fixed) {
    // Every player's total score and games against each opponent.
    const players = new Set([fixed.name]);
    for (const game of games) {
        players.add(game.white);
        players.add(game.black);
    }
    const ratings = {};
    for (const name of players) {
        ratings[name] = fixed.elo; // everyone starts at the anchor
    }

    // Step each rating towards where its expected score equals its real score.
    // Each step is small enough to stay stable; 20,000 rounds are plenty.
    const scale = Math.LN10 / 400;
    for (let round = 0; round < 20000; round++) {
        let largestChange = 0;
        for (const name of players) {
            if (name === fixed.name) {
                continue;
            }
            let actual = 0;
            let expected = 0;
            let information = 0;
            for (const game of games) {
                if (game.white !== name && game.black !== name) {
                    continue;
                }
                const isWhite = game.white === name;
                const opponent = isWhite ? game.black : game.white;
                const expectation = expectedScore(ratings[name], ratings[opponent]);
                actual += isWhite ? game.score : 1 - game.score;
                expected += expectation;
                information += expectation * (1 - expectation);
            }
            if (information === 0) {
                continue;
            }
            // Newton step, limited to 100 Elo so a 100 % score cannot run away.
            let change = (actual - expected) / (scale * information);
            change = Math.max(-100, Math.min(100, change));
            ratings[name] += change;
            largestChange = Math.max(largestChange, Math.abs(change));
        }
        if (largestChange < 0.01) {
            break;
        }
    }

    // The 95 % range: 1.96 standard errors, from each player's own games.
    // (A simple, slightly narrow estimate: it treats the opponents' ratings as known.)
    const result = {};
    for (const name of players) {
        let information = 0;
        let count = 0;
        for (const game of games) {
            if (game.white !== name && game.black !== name) {
                continue;
            }
            const opponent = game.white === name ? game.black : game.white;
            const expectation = expectedScore(ratings[name], ratings[opponent]);
            information += scale * scale * expectation * (1 - expectation);
            count++;
        }
        const halfRange = name === fixed.name || information === 0 ? 0 : 1.96 / Math.sqrt(information);
        result[name] = {
            elo: Math.round(ratings[name]),
            low: Math.round(ratings[name] - halfRange),
            high: Math.round(ratings[name] + halfRange),
            games: count,
        };
    }
    return result;
}
