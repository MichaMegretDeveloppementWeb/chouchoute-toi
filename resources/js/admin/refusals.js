/**
 * A gesture refused during an exchange, the account having lost the screen
 * since the page was drawn · the page is drawn again, and the server says the
 * refusal inside the office, with the way back, rather than Livewire framing
 * its own error page.
 */

const KEY = 'admin.refused-and-reloaded';

/** Ten seconds · a refusal that comes back this fast after a reload is not one a reload settles. */
const SETTLED_WITHIN_MS = 10_000;

/** Whether this page was already drawn again for a refusal, a moment ago. */
export function reloadedJustNow(storage, href, now) {
    try {
        const last = JSON.parse(storage.getItem(KEY) ?? 'null');

        return last !== null && last.href === href && now - last.at < SETTLED_WITHIN_MS;
    } catch (_e) {
        return false;
    }
}

/** Draws the page again on a refused exchange, once · a second refusal keeps Livewire's answer. */
export function reloadOnARefusal(livewire, {
    storage = window.sessionStorage,
    here = () => window.location.href,
    now = () => Date.now(),
    reload = () => window.location.reload(),
} = {}) {
    livewire.interceptRequest(({ onError }) => {
        onError(({ response, preventDefault }) => {
            if (response?.status !== 403 || reloadedJustNow(storage, here(), now())) {
                return;
            }

            preventDefault();
            storage.setItem(KEY, JSON.stringify({ href: here(), at: now() }));
            reload();
        });
    });
}
