/**
 * The player query pop-up on the organiser's desk (A116).
 *
 * How it works:
 * 1. Without this script the "Find players" button is a normal link: the
 *    page opens with the query box shown as a section (?find=1), and its
 *    results below it. Everything works that way.
 * 2. With this script the same button opens the box as a pop-up (a modal
 *    <dialog>) without leaving the page, and "Close" shuts it again.
 * 3. When the page was opened with results (?find=1), the box is shown as
 *    a pop-up at once, so the organiser sees the results where they asked.
 */

// --- Buttons that open and close a pop-up ---

document.querySelectorAll('[data-opens-dialog]').forEach(function (link) {
    link.addEventListener('click', function (event) {
        const dialog = document.getElementById(link.dataset.opensDialog);
        if (dialog === null || typeof dialog.showModal !== 'function') {
            return; // an old browser: follow the link instead
        }
        event.preventDefault();
        dialog.showModal();
    });
});

document.querySelectorAll('[data-closes-dialog]').forEach(function (link) {
    link.addEventListener('click', function (event) {
        const dialog = document.getElementById(link.dataset.closesDialog);
        if (dialog === null || typeof dialog.close !== 'function') {
            return;
        }
        event.preventDefault();
        dialog.close();
    });
});

// --- Results on the page: show them in the pop-up ---

const openDialog = document.querySelector('dialog.query-dialog[open]');
if (openDialog !== null && typeof openDialog.showModal === 'function') {
    openDialog.close();
    openDialog.showModal();
}
