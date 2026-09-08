
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
const ECART_DU_RAIL = 8;
const DELAI_OUVERTURE = 80;
const DELAI_FERMETURE = 180;

/** True when the sidebar is on its rail. The stylesheet is what says so. */
function surLeRail(element) {
    return getComputedStyle(element).getPropertyValue('--fb-rail').trim() === '1';
}

/**
 * The height of a panel not shown yet.
 *
 * Measured off-screen rather than after display: placed afterwards, the panel
 * would flash for one frame in the top left corner. `visibility` and not
 * `opacity`, which takes the element out of the paint without taking its box.
 */
function hauteurCachee(panneau) {
    const memoire = panneau.style.cssText;

    panneau.style.cssText = `${memoire};display:block;visibility:hidden`;
    const hauteur = panneau.offsetHeight;
    panneau.style.cssText = memoire;

    return hauteur;
}

/**
 * Places a panel right of the rail, level with what opens it.
 *
 * Pulled up when it would overflow the bottom of the window, and never above
 * it: a low section otherwise opened a panel showing only its first line.
 */
function ancrerAuRail(declencheur, hauteur) {
    const cadre = declencheur.getBoundingClientRect();
    const marge = 8;

    // The rail's edge and not the button's: the navigation is inset from its
    // sides, so a panel placed on the button started four pixels inside the
    // sidebar.
    const barre = declencheur.closest('.fb-sidebar');
    const bord = barre ? barre.getBoundingClientRect().right : cadre.right;

    return {
        gauche: Math.round(bord + ECART_DU_RAIL),
        haut: Math.round(Math.max(marge, Math.min(cadre.top, window.innerHeight - hauteur - marge))),
    };
}

document.addEventListener('alpine:init', () => {
    /** A section of the sidebar: accordion in place, panel on the rail. */
    Alpine.data('barreSection', (ouvertInitial, actif) => ({
        ouvert: ouvertInitial,
        volet: false,
        haut: 0,
        gauche: 0,
        minuterie: null,

        /* The pointer type of the last `pointerdown`, which `mener()` reads. */
        pointeur: '',

        /* The panel hands itself over: teleported under `body`, it no longer
           climbs back to the root that holds the refs. */
        panneau: null,

        init() {
            // The sidebar has to say where we are: the section carrying the
            // current page wins over what had been remembered.
            if (actif) {
                this.ouvert = true;
            }
        },

        rail() {
            return surLeRail(this.$root);
        },

        basculer() {
            if (! this.rail()) {
                this.ouvert = ! this.ouvert;

                return;
            }

            if (this.volet) {
                this.fermerLeVolet();
            } else {
                this.ouvrirLeVolet();
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
        mener(evenement) {
            if (! this.rail() || evenement.detail === 0) {
                return;
            }

            if ((evenement.pointerType || this.pointeur) !== 'touch') {
                return;
            }

            evenement.preventDefault();
            this.ouvrirLeVolet();
        },

        viser() {
            if (! this.rail()) {
                return;
            }

            clearTimeout(this.minuterie);
            this.minuterie = setTimeout(() => this.ouvrirLeVolet(), DELAI_OUVERTURE);
        },

        /* Time enough to cross the eight pixels between rail and panel. */
        garder() {
            clearTimeout(this.minuterie);
        },

        quitter() {
            clearTimeout(this.minuterie);
            this.minuterie = setTimeout(() => { this.volet = false; }, DELAI_FERMETURE);
        },

        ouvrirLeVolet() {
            if (! this.panneau) {
                return;
            }

            // Placed before shown and not the reverse, so the panel never
            // appears in the corner of the screen for one frame.
            const place = ancrerAuRail(this.$refs.declencheur, hauteurCachee(this.panneau));

            this.gauche = place.gauche;
            this.haut = place.haut;
            this.volet = true;
        },

        fermerLeVolet() {
            clearTimeout(this.minuterie);
            this.volet = false;
        },
    }));

    /**
     * A link's label, when the rail shows only its icon.
     *
     * One for the whole sidebar, listening to the hover of its links: putting
     * one on each link made dozens of them, the sidebar being rendered twice
     * and each section rendering its links twice more.
     */
    Alpine.data('barreInfobulle', () => ({
        ouvert: false,
        haut: 0,
        gauche: 0,
        minuterie: null,
        panneau: null,

        viser(evenement) {
            const lien = evenement.target.closest('[data-fb-title]');

            if (! lien || ! this.panneau || ! surLeRail(this.$root)) {
                return;
            }

            clearTimeout(this.minuterie);

            this.minuterie = setTimeout(() => {
                // The title is written into the element rather than bound: the
                // box is sized on it, and a binding would only be applied on
                // the next tick, so after the measurement.
                this.panneau.textContent = lien.dataset.fbTitle;

                const cadre = lien.getBoundingClientRect();
                const hauteur = hauteurCachee(this.panneau);

                this.gauche = ancrerAuRail(lien, hauteur).gauche;
                this.haut = Math.round(cadre.top + (cadre.height - hauteur) / 2);
                this.ouvert = true;
            }, DELAI_OUVERTURE);
        },

        quitter(evenement) {
            // `mouseout` also fires moving from one child of the same link to
            // another: close only when the cursor really left the sidebar or
            // changed link.
            if (evenement.relatedTarget && this.$root.contains(evenement.relatedTarget)
                && evenement.relatedTarget.closest('[data-fb-title]') === evenement.target.closest('[data-fb-title]')) {
                return;
            }

            clearTimeout(this.minuterie);
            this.ouvert = false;
        },
    }));
});
