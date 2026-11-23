/**
 * The Play page's live lobby (G-16, A46).
 *
 * Asks GET /play/news every few seconds while the page is open:
 * - the asking itself keeps my open timed challenges in the lobby (the
 *   server lists them only while their poster is here);
 * - when someone accepted one of them, the answer names the new game and
 *   the page goes there at once (the clock is already running);
 * - when the list of open challenges changed, the page reloads to show it.
 *
 * Uses the same transport as the game page (transport.js).
 */

import {startTransport} from '../board/transport.js';

const script = document.getElementById('lobby-script');
const basePath = script.dataset.basePath;
const shownOpenIds = script.dataset.openIds;

startTransport({
    url: () => script.dataset.newsUrl,
    onNews: (answer) => {
        if (answer.new_game_id) {
            window.location.href = basePath + '/games/' + answer.new_game_id;
            return;
        }
        // Do not reload while the player is filling in a form.
        const isTyping = document.activeElement && ['INPUT', 'SELECT', 'TEXTAREA'].includes(document.activeElement.tagName);
        if (Array.isArray(answer.open_ids) && answer.open_ids.join(',') !== shownOpenIds && !isTyping) {
            window.location.reload();
        }
    },
});
