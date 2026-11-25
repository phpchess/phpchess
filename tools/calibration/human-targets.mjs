/**
 * What people of a rating do: the targets for the computer players' slips
 * (development only).
 *
 * Feature AI-05, decisions A55 and A56. Reads the people's profile
 * (storage/calibration/profile-people.json, written by
 * tools/human-profile.mjs people) and gives, for any rating, the share of
 * moves that are mistakes, blunders and giveaways, and the average loss.
 *
 * Smoothing: a band's value is the average of the band and the bands either
 * side (the bands are only 60 games each, so single bands are noisy).
 * Below 600 there are no people's games: the straight line through the
 * 600-1000 bands is continued downwards.
 */

// --- The bands used for the downward trend ---

const TREND_BANDS = [600, 700, 800, 900, 1000];
const LOWEST_BAND = 600;
const HIGHEST_BAND = 2100;

/** The smoothed value of one number (averageLoss, mistakes, ...) at a band. */
function smoothedAt(people, band, key) {
    const neighbours = [band - 100, band, band + 100].filter((each) => people[each]);
    const total = neighbours.reduce((sum, each) => sum + people[each][key], 0);
    return total / neighbours.length;
}

/** The value on the straight line through the trend bands (least squares). */
function trendAt(people, elo, key) {
    const middleX = TREND_BANDS.reduce((sum, band) => sum + band, 0) / TREND_BANDS.length;
    const middleY = TREND_BANDS.reduce((sum, band) => sum + people[band][key], 0) / TREND_BANDS.length;
    let together = 0;
    let spread = 0;
    for (const band of TREND_BANDS) {
        together += (band - middleX) * (people[band][key] - middleY);
        spread += (band - middleX) ** 2;
    }
    return middleY + (together / spread) * (elo - middleX);
}

/**
 * The people's numbers at a rating: { averageLoss, mistakes, blunders,
 * giveaways }, shares in per cent, as the profile counts them (a blunder is
 * also a mistake; a giveaway is usually also a blunder).
 */
export function peopleAt(people, elo) {
    const target = {};
    for (const key of ["averageLoss", "mistakes", "blunders", "giveaways"]) {
        if (elo < LOWEST_BAND) {
            target[key] = trendAt(people, elo, key);
        } else {
            const band = Math.min(HIGHEST_BAND, Math.round(elo / 100) * 100);
            target[key] = smoothedAt(people, band, key);
        }
    }
    return target;
}

/**
 * The rating whose people lose this much per move on average: "plays like a
 * ___ player". Between two bands the rating is read off the straight line
 * between them; outside the measured bands it is "below 600" or "above 2100"
 * (returned as null).
 */
export function playsLike(people, averageLoss) {
    const bands = Object.keys(people).map(Number).sort((first, second) => first - second);
    for (let index = 0; index < bands.length - 1; index++) {
        const lower = smoothedAt(people, bands[index], "averageLoss");
        const higher = smoothedAt(people, bands[index + 1], "averageLoss");
        const between = (averageLoss <= lower && averageLoss >= higher) || (averageLoss >= lower && averageLoss <= higher);
        if (between && lower !== higher) {
            return Math.round(bands[index] + 100 * (lower - averageLoss) / (lower - higher));
        }
    }
    return null;
}
