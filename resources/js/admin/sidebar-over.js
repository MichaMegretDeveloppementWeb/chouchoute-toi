/**
 * The bar over the content, between lg and 1400 pixels · the ☰ opens it whole
 * over the page and remembers nothing, and a click beside it, a link, Escape or
 * the ☰ again folds it back. Above and below, the kit's own command is left
 * alone. The stylesheet does the drawing (`admin/sidebar.css`).
 */

const BAND = '(64rem <= width < 87.5rem)';
const OVER = 'data-admin-sidebar-over';
const STATE = 'data-ui-sidebar';
const TOGGLE = '[data-ui-sidebar-toggle]';

const frame = () => document.querySelector('.ui-sidebar-frame');

/** Whether the bar is open over the content. */
export function isOver(box) {
    return box?.hasAttribute(OVER) ?? false;
}

function sayTheState(open) {
    document.querySelectorAll(TOGGLE).forEach((button) => button.setAttribute('aria-expanded', open ? 'true' : 'false'));
}

/** Opens the bar over the content through the kit's open state, keeping the one it replaces. */
export function openOver(box) {
    box.setAttribute(OVER, box.getAttribute(STATE) ?? '');
    box.setAttribute(STATE, 'expanded');
    sayTheState(true);
}

/** Folds the bar back, the kit's state as it was before. */
export function closeOver(box) {
    const was = box.getAttribute(OVER);

    box.removeAttribute(OVER);

    if (was) {
        box.setAttribute(STATE, was);
    } else {
        box.removeAttribute(STATE);
    }

    sayTheState(false);
}

/** Hands the ☰ the bar over the content while the window stays in the band. */
export function holdTheBarOverTheContent(band = window.matchMedia(BAND)) {
    const onClick = (event) => {
        const box = frame();

        if (! band.matches || ! box || ! (event.target instanceof Element)) {
            return;
        }

        if (event.target.closest(TOGGLE)) {
            event.preventDefault();
            event.stopImmediatePropagation();

            if (isOver(box)) {
                closeOver(box);
            } else {
                openOver(box);
            }

            return;
        }

        if (! isOver(box)) {
            return;
        }

        // A link of the bar leads away and the next page opens folded · a click
        // beside the bar lands on the veil, and does nothing more than fold it.
        if (! event.target.closest('.ui-sidebar')) {
            event.preventDefault();
            event.stopImmediatePropagation();
            closeOver(box);
        } else if (event.target.closest('a[href]')) {
            closeOver(box);
        }
    };

    const onKey = (event) => {
        const box = frame();

        if (event.key === 'Escape' && isOver(box)) {
            closeOver(box);
            document.querySelector(TOGGLE)?.focus();
        }
    };

    const onBand = () => {
        const box = frame();

        if (! band.matches && isOver(box)) {
            closeOver(box);
        }
    };

    document.addEventListener('click', onClick, true);
    document.addEventListener('keydown', onKey);
    band.addEventListener('change', onBand);

    return () => {
        document.removeEventListener('click', onClick, true);
        document.removeEventListener('keydown', onKey);
        band.removeEventListener('change', onBand);
    };
}
