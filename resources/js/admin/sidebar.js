
/* The state lives on `<html>` in `data-fb-sidebar`, written before paint by the
   layout script; the stylesheet derives both the sidebar's width and the
   content's inset from it. */
window.toggleSidebar = function () {
    const collapsed = document.documentElement.dataset.fbSidebar === 'collapsed';
    const wanted = collapsed ? 'expanded' : 'collapsed';

    document.documentElement.dataset.fbSidebar = wanted;

    try {
        localStorage.setItem('fb-sidebar', wanted);
    } catch (e) {
        /* Private browsing: the state holds for the page, which is already
           something. */
    }
};

/* What the rail has to make readable: a link's label, a section's children.
   Both are `fixed` and teleported to `body`, the sidebar being `overflow-clip`.
   Their position is computed from the trigger as they open, and they close on
   scroll: a fixed panel does not follow what opened it. */
const RAIL_GAP = 8;
const OPEN_DELAY = 80;
const CLOSE_DELAY = 180;

/** True when the sidebar is on its rail. The stylesheet is what says so. */
function onTheRail(element) {
    return getComputedStyle(element).getPropertyValue('--fb-rail').trim() === '1';
}

/**
 * The height of a panel not shown yet.
 *
 * Measured off-screen rather than after display: placed afterwards, the panel
 * would flash for one frame in the top left corner. `visibility` and not
 * `opacity`, which takes the element out of the paint without taking its box.
 */
function hiddenHeight(panel) {
    const saved = panel.style.cssText;

    panel.style.cssText = `${saved};display:block;visibility:hidden`;
    const height = panel.offsetHeight;
    panel.style.cssText = saved;

    return height;
}

/**
 * Places a panel right of the rail, level with what opens it.
 *
 * Pulled up when it would overflow the bottom of the window, and never above
 * it: a low section otherwise opened a panel showing only its first line.
 */
function anchorToRail(trigger, height) {
    const box = trigger.getBoundingClientRect();
    const margin = 8;

    // The rail's edge and not the button's: the navigation is inset from its
    // sides, so a panel placed on the button started four pixels inside the
    // sidebar.
    const sidebar = trigger.closest('.fb-sidebar');
    const edge = sidebar ? sidebar.getBoundingClientRect().right : box.right;

    return {
        left: Math.round(edge + RAIL_GAP),
        top: Math.round(Math.max(margin, Math.min(box.top, window.innerHeight - height - margin))),
    };
}

document.addEventListener('alpine:init', () => {
    /** A section of the sidebar: accordion in place, panel on the rail. */
    Alpine.data('sidebarSection', (initiallyOpen, active) => ({
        isOpen: initiallyOpen,
        isPanelOpen: false,
        top: 0,
        left: 0,
        timer: null,

        /* The pointer type of the last `pointerdown`, which `lead()` reads. */
        pointerType: '',

        /* The panel hands itself over: teleported under `body`, it no longer
           climbs back to the root that holds the refs. */
        panel: null,

        init() {
            // The sidebar has to say where we are: the section carrying the
            // current page wins over what had been remembered.
            if (active) {
                this.isOpen = true;
            }
        },

        isRail() {
            return onTheRail(this.$root);
        },

        toggle() {
            if (! this.isRail()) {
                this.isOpen = ! this.isOpen;

                return;
            }

            if (this.isPanelOpen) {
                this.closePanel();
            } else {
                this.openPanel();
            }
        },

        /*
         * On the rail a finger press stands for a hover: it opens the panel
         * instead of following the link, a touch screen having no hover. Mouse
         * and keyboard do follow it.
         *
         * `detail` is zero on a keyboard activation, which goes through no
         * pointer: without this guard the type remembered from an earlier press
         * would be applied to it.
         */
        lead(event) {
            if (! this.isRail() || event.detail === 0) {
                return;
            }

            if ((event.pointerType || this.pointerType) !== 'touch') {
                return;
            }

            event.preventDefault();
            this.openPanel();
        },

        aim() {
            if (! this.isRail()) {
                return;
            }

            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.openPanel(), OPEN_DELAY);
        },

        /* Time enough to cross the eight pixels between rail and panel. */
        keep() {
            clearTimeout(this.timer);
        },

        leave() {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => { this.isPanelOpen = false; }, CLOSE_DELAY);
        },

        openPanel() {
            if (! this.panel) {
                return;
            }

            // Placed before shown and not the reverse, so the panel never
            // appears in the corner of the screen for one frame.
            const spot = anchorToRail(this.$refs.trigger, hiddenHeight(this.panel));

            this.left = spot.left;
            this.top = spot.top;
            this.isPanelOpen = true;
        },

        closePanel() {
            clearTimeout(this.timer);
            this.isPanelOpen = false;
        },
    }));

    /**
     * A link's label, when the rail shows only its icon.
     *
     * One for the whole sidebar, listening to the hover of its links: putting
     * one on each link made dozens of them, the sidebar being rendered twice
     * and each section rendering its links twice more.
     */
    Alpine.data('sidebarTooltip', () => ({
        isOpen: false,
        top: 0,
        left: 0,
        timer: null,
        panel: null,

        aim(event) {
            const link = event.target.closest('[data-fb-title]');

            if (! link || ! this.panel || ! onTheRail(this.$root)) {
                return;
            }

            clearTimeout(this.timer);

            this.timer = setTimeout(() => {
                // The title is written into the element rather than bound: the
                // box is sized on it, and a binding would only be applied on
                // the next tick, so after the measurement.
                this.panel.textContent = link.dataset.fbTitle;

                const box = link.getBoundingClientRect();
                const height = hiddenHeight(this.panel);

                this.left = anchorToRail(link, height).left;
                this.top = Math.round(box.top + (box.height - height) / 2);
                this.isOpen = true;
            }, OPEN_DELAY);
        },

        leave(event) {
            // `mouseout` also fires moving from one child of the same link to
            // another: close only when the cursor really left the sidebar or
            // changed link.
            if (event.relatedTarget && this.$root.contains(event.relatedTarget)
                && event.relatedTarget.closest('[data-fb-title]') === event.target.closest('[data-fb-title]')) {
                return;
            }

            clearTimeout(this.timer);
            this.isOpen = false;
        },
    }));
});
