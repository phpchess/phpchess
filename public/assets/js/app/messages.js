/**
 * Live messages in an open conversation (C-04, A41).
 *
 * While the page is open and visible, it asks the server every 5 seconds
 * for messages newer than the last one shown (GET /messages/{name}/new?after=ID)
 * and adds them to the list. A hidden tab asks every 30 seconds, to spare
 * the server and the phone's battery. Messages are added with textContent,
 * so a message can never become HTML.
 */

const list = document.getElementById('message-list');
const VISIBLE_SECONDS = 5;
const HIDDEN_SECONDS = 30;

let lastId = list ? parseInt(list.dataset.lastId || '0', 10) : 0;

function addMessage(message) {
    const item = document.createElement('li');
    item.className = message.mine ? 'message is-mine' : 'message';
    const text = document.createElement('p');
    text.textContent = message.body;
    const time = document.createElement('span');
    time.className = 'muted';
    time.textContent = message.created_at.substring(0, 16);
    item.append(text, time);
    list.append(item);
}

async function askForNews() {
    try {
        const response = await fetch(list.dataset.pollUrl + '?after=' + lastId, {headers: {'Accept': 'application/json'}, credentials: 'same-origin'});
        if (response.ok) {
            const answer = await response.json();
            for (const message of answer.messages) {
                addMessage(message);
                lastId = Math.max(lastId, message.id);
            }
            if (answer.messages.length > 0) {
                list.lastElementChild.scrollIntoView({block: 'nearest'});
            }
        }
    } catch (error) {
        // Offline for a moment: try again next time.
    }
    window.setTimeout(askForNews, (document.hidden ? HIDDEN_SECONDS : VISIBLE_SECONDS) * 1000);
}

if (list) {
    window.setTimeout(askForNews, VISIBLE_SECONDS * 1000);
}
