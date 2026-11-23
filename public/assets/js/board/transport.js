/**
 * The transport for live games (G-10, API-03): how the page learns about
 * news from the server.
 *
 * The interface (any transport offers these, so another kind, e.g. one
 * that keeps a connection open, could replace this one without changing
 * the game page):
 *
 *   const transport = startTransport({url: () => '...', onNews: (answer) => {...}});
 *   transport.askNow();   ask at once (a clock reached zero, the tab came back)
 *   transport.stop();     no more questions (the game is over)
 *
 * This one is short polling (A1, A46): plain fetch requests, no WebSocket,
 * so it works on any shared host. The server says in each answer how long
 * to wait before the next question ("next_poll_ms"): about a second while
 * the opponent is to move in a timed game, longer otherwise. While the tab
 * is hidden it waits at least HIDDEN_WAIT_MS; when the tab becomes visible
 * again it asks at once.
 */

const DEFAULT_WAIT_MS = 4000;
const HIDDEN_WAIT_MS = 30000;

export function startTransport({url, onNews}) {
    let timer = null;
    let waitMs = DEFAULT_WAIT_MS;
    let stopped = false;
    let asking = false;

    async function askForNews() {
        if (stopped || asking) {
            return;
        }
        asking = true;
        try {
            const response = await fetch(url(), {headers: {'Accept': 'application/json'}, credentials: 'same-origin'});
            if (response.ok) {
                const answer = await response.json();
                if (typeof answer.next_poll_ms === 'number') {
                    waitMs = answer.next_poll_ms;
                }
                onNews(answer);
            }
        } catch (error) {
            // Offline for a moment: simply try again next time.
        }
        asking = false;
        planNextQuestion();
    }

    function planNextQuestion() {
        clearTimeout(timer);
        if (stopped) {
            return;
        }
        const wait = document.hidden ? Math.max(waitMs, HIDDEN_WAIT_MS) : waitMs;
        timer = setTimeout(askForNews, wait);
    }

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            clearTimeout(timer);
            askForNews();
        }
    });

    // The first question goes out at once: in a timed game the server
    // starts this player's clock when the page first receives the move.
    askForNews();

    return {
        askNow() {
            clearTimeout(timer);
            askForNews();
        },
        stop() {
            stopped = true;
            clearTimeout(timer);
        },
    };
}
