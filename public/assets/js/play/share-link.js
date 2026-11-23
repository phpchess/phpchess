/**
 * The "Share" button for invite links (G-21): opens the phone's share sheet
 * (Web Share API), so the link goes to a chat app in one tap. Where the
 * browser has no share sheet, the button stays hidden and the link field
 * can be copied as usual. A click in the link field selects all of it.
 */

function setUpShareButton() {
    const button = document.getElementById('share-invite');
    if (!button || typeof navigator.share !== 'function') {
        return;
    }
    button.hidden = false;
    button.addEventListener('click', () => {
        navigator.share({title: button.dataset.shareTitle, url: button.dataset.shareUrl}).catch(() => {
            // The person closed the share sheet: nothing to do.
        });
    });
}

function selectLinkOnFocus() {
    const field = document.getElementById('invite-link');
    if (field) {
        field.addEventListener('focus', () => field.select());
    }
}

setUpShareButton();
selectLinkOnFocus();
