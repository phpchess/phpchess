/**
 * The site-wide news poll (B21, A63), loaded on every page for members
 * (except the Play page, games and the strength test, which ask themselves).
 *
 * Every few seconds (the server says how long in "next_poll_ms"; at least
 * 60 seconds while the tab is hidden) it asks GET /news and then:
 *   - a timed challenge for me: shows the pop-up (templates/layout.php)
 *     with Accept and Decline; both are plain forms, so the server does
 *     the work and Accept leads straight to the game;
 *   - a new timed game I have not opened (my challenge was accepted):
 *     goes to that game;
 *   - the number of unread messages: updates the badge on Messages.
 *
 * It uses the same transport as the game page (board/transport.js).
 */

import {startTransport} from '../board/transport.js';

const basePath = document.body.dataset.basePath;
const popUp = document.getElementById('challenge-pop-up');

/** The challenge the pop-up shows (or showed and the member closed), so it is not shown twice. */
let shownChallengeId = null;

startTransport({
    url: () => basePath + '/news',
    hiddenWaitMs: 60000,
    onNews: (answer) => {
        if (answer.new_game_id) {
            window.location.href = basePath + '/games/' + answer.new_game_id;
            return;
        }
        if (answer.challenge && answer.challenge.id !== shownChallengeId) {
            showChallenge(answer.challenge);
        }
        showUnreadMessages(answer.unread_messages);
    },
});

// --- The challenge pop-up ---

function showChallenge(challenge) {
    shownChallengeId = challenge.id;
    const texts = popUp.dataset;
    const colourTexts = {white: texts.textColourWhite, black: texts.textColourBlack, random: texts.textColourRandom};

    document.getElementById('challenge-pop-up-title').textContent = texts.textTitle.replace('{name}', challenge.from);
    document.getElementById('challenge-pop-up-details').textContent = texts.textDetails
        .replace('{time}', challenge.time_control)
        .replace('{colour}', colourTexts[challenge.colour] || colourTexts.random)
        .replace('{rated}', challenge.rated ? texts.textRated : texts.textUnrated);
    document.getElementById('challenge-pop-up-accept').action = basePath + '/challenges/' + challenge.id + '/accept';
    document.getElementById('challenge-pop-up-decline').action = basePath + '/challenges/' + challenge.id + '/decline';

    if (!popUp.open) {
        popUp.show();
    }
}

// --- The badge on Messages ---

function showUnreadMessages(count) {
    const link = document.querySelector('a[href="' + basePath + '/messages"]');
    if (!link || typeof count !== 'number') {
        return;
    }
    let badge = link.querySelector('.badge');
    if (count === 0) {
        if (badge) {
            badge.remove();
        }
        return;
    }
    if (!badge) {
        badge = document.createElement('span');
        badge.className = 'badge';
        link.append(' ', badge);
    }
    badge.textContent = String(count);
}
