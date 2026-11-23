/**
 * Live updates for the game page (G-10): asks the server for news every few
 * seconds while the page is open, so the opponent's moves appear without a
 * reload. Each request also tells the server "this player is watching", so
 * no notice or email is sent for moves seen live.
 *
 * Plain polling with fetch, no WebSocket: it works on any shared host (A1).
 * While the tab is hidden it asks less often, and when it becomes visible
 * again it asks at once.
 *
 * Used by game-page.js:
 *   startLiveUpdates({stateUrl: () => '...state?since=5', onNews: (state) => {...}})
 */

const SECONDS_WHILE_VISIBLE = 4;
const SECONDS_WHILE_HIDDEN = 30;

export function startLiveUpdates({stateUrl, onNews}) {
    let timer = null;

    async function askForNews() {
        try {
            const response = await fetch(stateUrl(), {headers: {'Accept': 'application/json'}, credentials: 'same-origin'});
            if (response.ok) {
                onNews(await response.json());
            }
        } catch (error) {
            // Offline for a moment: simply try again next time.
        }
        planNextQuestion();
    }

    function planNextQuestion() {
        clearTimeout(timer);
        const seconds = document.hidden ? SECONDS_WHILE_HIDDEN : SECONDS_WHILE_VISIBLE;
        timer = setTimeout(askForNews, seconds * 1000);
    }

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            clearTimeout(timer);
            askForNews();
        }
    });

    planNextQuestion();
}
