/**
 * Busy buttons, on every page: when a form is sent, the button that sent it
 * shows a small spinner and can not be pressed again until the next page
 * comes. So a player sees that the press arrived and that the server is
 * working, and a second press can not send the form twice.
 *
 * The look is in base.css (.is-busy). The pressed look (button:active) needs
 * no script. Pages work the same without this script, only without the spinner.
 */

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (form.dataset.busy === 'yes') {
        event.preventDefault();   // already sent once: wait for the answer
        return;
    }
    form.dataset.busy = 'yes';

    const button = event.submitter;
    if (button) {
        button.classList.add('is-busy');
        button.setAttribute('aria-disabled', 'true');
    }
});

// Coming back with the browser's Back button can show the old page from memory: make its buttons usable again.
window.addEventListener('pageshow', () => {
    document.querySelectorAll('form[data-busy]').forEach((form) => delete form.dataset.busy);
    document.querySelectorAll('.is-busy').forEach((button) => {
        button.classList.remove('is-busy');
        button.removeAttribute('aria-disabled');
    });
});
