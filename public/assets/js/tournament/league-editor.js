/**
 * The League editor (T-05, A15, A103): place players in divisions by
 * dragging their cards, or with the arrow buttons on each card.
 *
 * How it works:
 * 1. Every list (one per division, plus "not placed") is a SortableJS list
 *    in one shared group, so cards can move between them (mouse or finger).
 * 2. After every change the whole placement is sent to the server as one
 *    hidden-field form: division_1=12,7,30 ... and unplaced=5,9. The server
 *    checks it (LeagueDivisions::save) and answers with JSON.
 * 3. Before each change the old placement is kept, so "Undo" can put it
 *    back (and save that) until the season starts.
 *
 * The page works without this script too: it then only shows the divisions.
 * Needs /assets/vendor/sortablejs/Sortable.min.js loaded first (A16: local).
 */

// --- Finding the editor on the page ---

const editor = document.querySelector('[data-league-editor]');
if (editor !== null && editor.dataset.editable === 'yes' && typeof Sortable !== 'undefined') {
    setUpEditor(editor);
}

function setUpEditor(board) {
    const lists = Array.from(board.querySelectorAll('[data-league-list]'));
    const undoButton = document.querySelector('[data-league-undo]');
    const status = document.querySelector('[data-league-status]');
    const history = [];

    // --- Dragging (SortableJS) ---
    let placementBeforeDrag = null;
    lists.forEach(function (list) {
        Sortable.create(list, {
            group: 'league',
            animation: 150,
            // On a phone a short press picks a card up, so a normal swipe still scrolls the page.
            delay: 150,
            delayOnTouchOnly: true,
            onStart: function () {
                placementBeforeDrag = readPlacement(lists);
            },
            onEnd: function () {
                rememberAndSave(placementBeforeDrag);
            },
        });
    });

    // --- The arrow buttons on each card ---
    board.addEventListener('click', function (event) {
        const button = event.target.closest('[data-league-move]');
        if (button === null) {
            return;
        }
        const card = button.closest('[data-user-id]');
        const before = readPlacement(lists);
        if (moveCard(card, button.dataset.leagueMove, lists)) {
            rememberAndSave(before);
            button.focus();
        }
    });

    // --- Undo ---
    if (undoButton !== null) {
        undoButton.addEventListener('click', function () {
            const previous = history.pop();
            if (previous === undefined) {
                return;
            }
            showPlacement(previous, lists);
            save(previous);
            undoButton.disabled = history.length === 0;
        });
    }

    updateCounts(lists);

    // --- Keeping the old placement and saving the new one ---

    function rememberAndSave(before) {
        const after = readPlacement(lists);
        if (before === null || JSON.stringify(before) === JSON.stringify(after)) {
            return;
        }
        history.push(before);
        if (undoButton !== null) {
            undoButton.disabled = false;
        }
        updateCounts(lists);
        save(after);
    }

    function save(placement) {
        status.textContent = board.dataset.textSaving;
        const body = new URLSearchParams({_csrf: board.dataset.csrfToken});
        Object.keys(placement).forEach(function (division) {
            const fieldName = division === '0' ? 'unplaced' : 'division_' + division;
            body.append(fieldName, placement[division].join(','));
        });
        fetch(board.dataset.saveUrl, {
            method: 'POST',
            headers: {'Accept': 'application/json'},
            body: body,
        }).then(function (response) {
            return response.json().then(function (answer) {
                status.textContent = response.ok ? board.dataset.textSaved : (answer.error ? answer.error.message : board.dataset.textFailed);
            });
        }).catch(function () {
            status.textContent = board.dataset.textFailed;
        });
    }
}

// --- Reading and showing a placement: division number => list of user ids, top seed first ---

function readPlacement(lists) {
    const placement = {};
    lists.forEach(function (list) {
        placement[list.dataset.division] = Array.from(list.children).map(function (card) {
            return card.dataset.userId;
        });
    });
    return placement;
}

function showPlacement(placement, lists) {
    const cards = {};
    lists.forEach(function (list) {
        Array.from(list.children).forEach(function (card) {
            cards[card.dataset.userId] = card;
        });
    });
    lists.forEach(function (list) {
        (placement[list.dataset.division] || []).forEach(function (userId) {
            if (cards[userId] !== undefined) {
                list.appendChild(cards[userId]);
            }
        });
    });
    updateCounts(lists);
}

// --- The arrow buttons: up, down, to the division above ("previous"), below ("next") ---

function moveCard(card, direction, lists) {
    const list = card.parentElement;
    if (direction === 'up' && card.previousElementSibling !== null) {
        list.insertBefore(card, card.previousElementSibling);
        return true;
    }
    if (direction === 'down' && card.nextElementSibling !== null) {
        list.insertBefore(card.nextElementSibling, card);
        return true;
    }
    // The columns are in page order: division 1, 2, ..., then "not placed".
    const index = lists.indexOf(list);
    const target = direction === 'previous' ? lists[index - 1] : (direction === 'next' ? lists[index + 1] : undefined);
    if (target === undefined) {
        return false;
    }
    target.appendChild(card);
    return true;
}

// --- How many players each column has ---

function updateCounts(lists) {
    lists.forEach(function (list) {
        const count = list.closest('section').querySelector('[data-league-count]');
        if (count !== null) {
            count.textContent = '(' + list.children.length + ')';
        }
    });
}
