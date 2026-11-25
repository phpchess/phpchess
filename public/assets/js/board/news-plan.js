/**
 * What a piece of game news changes on the page (B23: no flicker).
 *
 * game-page.js asks this for every answer from the server, and then touches
 * only what the plan says. A poll that brings news but no move (an offer,
 * the opponent's presence) must leave the board alone and never reload the
 * page; tests/js/news-plan.test.mjs checks exactly that.
 *
 * Kept apart from game-page.js (which needs a browser) so Node can test it.
 *
 *   planNews(oldState, newState) returns:
 *     moveBoard     a new move: rebuild the positions and show the new one
 *     swapParts     offers, the abort button or the game's
 *                   end changed: fetch GET /games/{id}/parts and swap the
 *                   status line, game buttons and offers
 *     gameEnded     the game was running and is over now: stop polling
 *     reloadPage    the players agreed on a new pace (G-23), which changes
 *                   the clocks in the header: the one rare case that still
 *                   reloads the page (once, by agreement, not during play)
 */

export function planNews(oldState, newState) {
    if (newState.unchanged) {
        // A short answer: only the clocks and presence were news.
        return {moveBoard: false, swapParts: false, gameEnded: false, reloadPage: false};
    }

    const offersChanged = newState.draw_offered_by !== oldState.draw_offered_by
        || newState.rematch_offered_by !== oldState.rematch_offered_by
        || JSON.stringify(newState.pace_offer) !== JSON.stringify(oldState.pace_offer);
    const gameEnded = oldState.state === 'running' && newState.state !== 'running';
    // Abort is possible only before the first move: after it, the button must go.
    const abortButtonMustGo = oldState.ply === 0 && newState.ply > 0;

    return {
        moveBoard: newState.ply !== oldState.ply,
        swapParts: offersChanged || gameEnded || abortButtonMustGo,
        gameEnded,
        reloadPage: newState.time_control !== oldState.time_control,
    };
}

/**
 * Whether the board already shows this position (our own move, already
 * played on the board by dragging): then it is not set again, which would
 * animate the same move twice.
 *
 * boardFen is cm-chessboard's getPosition() (pieces only); fullFen is a
 * whole FEN from the server.
 */
export function boardShows(boardFen, fullFen) {
    return boardFen === fullFen.split(' ')[0];
}
