/*
 * Falcon UI Kit — Entrypoint JS
 *
 * Ce fichier est le point d'entree JS pour le back-office.
 * Il charge les utilitaires du kit (theme, sidebar, toast, modal)
 * et Chart.js pour le composant <x-ui.chart>.
 *
 * Utilisez @vite(['resources/css/ui-kit.css', 'resources/js/ui-kit.js'])
 * dans votre layout back-office.
 */
import '../../vendor/falcon/ui-kit/resources/js/ui-kit.js';
import Chart from 'chart.js/auto';
window.Chart = Chart;
/*
 * La barre laterale : ouverte ou repliee, et rien entre les deux.
 *
 * L'etat vit sur `<html>` en `data-fb-sidebar`, pose avant la peinture par le
 * script du layout, et la feuille en tire la largeur de la barre comme celle du
 * contenu. Retenu comme le theme : on ne redemande pas a chaque page ce qui a
 * ete decide une fois.
 *
 * Le survol ne deploie plus rien, a aucune largeur. Il le faisait entre 1024 et
 * 1500 px, et l'entree qu'on visait descendait de 182 px pendant qu'on avancait
 * vers elle.
 */
window.toggleSidebar = function () {
    const collapsed = document.documentElement.dataset.fbSidebar === 'collapsed';
    const wanted = collapsed ? 'expanded' : 'collapsed';

    document.documentElement.dataset.fbSidebar = wanted;

    try {
        localStorage.setItem('fb-sidebar', wanted);
    } catch (e) {
        /* Navigation privee : l'etat vaut pour la page, et c'est deja ca. */
    }
};

/*
 * Ce que le rail doit rendre lisible : le libelle d'un lien, les enfants d'une
 * section.
 *
 * Les deux se posent en `fixed` et se teleportent vers `body`, la barre etant
 * en `overflow-clip`. Leur position se calcule sur le declencheur au moment ou
 * ils s'ouvrent, et ils se ferment au defilement : un panneau fixe ne suit pas
 * ce qui l'a ouvert.
 */
const ECART_DU_RAIL = 8;
const DELAI_OUVERTURE = 80;
const DELAI_FERMETURE = 180;

/** Vrai quand la barre est sur son rail. C'est la feuille qui le dit. */
function surLeRail(element) {
    return getComputedStyle(element).getPropertyValue('--fb-rail').trim() === '1';
}

/**
 * La hauteur d'un panneau qu'on n'a pas encore montre.
 *
 * Mesure hors de l'ecran plutot qu'apres l'affichage : place ensuite, le
 * panneau apparaitrait une image au coin superieur gauche avant de rejoindre sa
 * position. `visibility` et non `opacity` : elle sort l'element du rendu sans
 * lui retirer sa boite.
 */
function hauteurCachee(panneau) {
    const memoire = panneau.style.cssText;

    panneau.style.cssText = `${memoire};display:block;visibility:hidden`;
    const hauteur = panneau.offsetHeight;
    panneau.style.cssText = memoire;

    return hauteur;
}

/**
 * Pose un panneau a droite du rail, a la hauteur de ce qui l'ouvre.
 *
 * Rabattu vers le haut quand il depasserait le bas de la fenetre, et jamais
 * au-dessus d'elle : une section basse ouvrait sinon un volet dont on ne voyait
 * que la premiere ligne.
 */
function ancrerAuRail(declencheur, hauteur) {
    const cadre = declencheur.getBoundingClientRect();
    const marge = 8;

    // Le bord du rail, et non celui du bouton : la navigation est en retrait de
    // ses cotes, et un panneau pose sur le bouton commencait quatre pixels a
    // l'interieur de la barre.
    const barre = declencheur.closest('.fb-sidebar');
    const bord = barre ? barre.getBoundingClientRect().right : cadre.right;

    return {
        gauche: Math.round(bord + ECART_DU_RAIL),
        haut: Math.round(Math.max(marge, Math.min(cadre.top, window.innerHeight - hauteur - marge))),
    };
}

document.addEventListener('alpine:init', () => {
    /** Une section de la barre : accordeon sur place, volet sur le rail. */
    Alpine.data('barreSection', (ouvertInitial, actif) => ({
        ouvert: ouvertInitial,
        volet: false,
        haut: 0,
        gauche: 0,
        minuterie: null,

        /* Le panneau se donne lui-meme : teleporte sous `body`, il ne remonte
           plus jusqu'a la racine qui tient les references. */
        panneau: null,

        init() {
            // La barre doit dire ou l'on est : la section qui porte la page
            // l'emporte sur ce qui avait ete retenu.
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

        viser() {
            if (! this.rail()) {
                return;
            }

            clearTimeout(this.minuterie);
            this.minuterie = setTimeout(() => this.ouvrirLeVolet(), DELAI_OUVERTURE);
        },

        /* Le temps de traverser les huit pixels qui separent le rail du volet. */
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

            // Place avant de montrer, et non l'inverse : le panneau ne parait
            // jamais au coin de l'ecran le temps d'une image.
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
     * Le libelle d'un lien, quand le rail ne montre que son icone.
     *
     * Une seule pour toute la barre, qui ecoute le survol de ses liens : la
     * poser sur chaque lien en faisait quatre-vingt-quatorze, la barre etant
     * rendue deux fois et chaque section rendant ses liens deux fois de plus.
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
                // Le titre s'ecrit dans l'element plutot que par une liaison :
                // la boite se dimensionne sur lui, et une liaison ne serait
                // appliquee qu'au tour suivant, donc apres la mesure.
                this.panneau.textContent = lien.dataset.fbTitle;

                const cadre = lien.getBoundingClientRect();
                const hauteur = hauteurCachee(this.panneau);

                this.gauche = ancrerAuRail(lien, hauteur).gauche;
                this.haut = Math.round(cadre.top + (cadre.height - hauteur) / 2);
                this.ouvert = true;
            }, DELAI_OUVERTURE);
        },

        quitter(evenement) {
            // `mouseout` part aussi en passant d'un enfant a l'autre du meme
            // lien : on ne ferme que si le curseur a bien quitte la barre ou
            // change de lien.
            if (evenement.relatedTarget && this.$root.contains(evenement.relatedTarget)
                && evenement.relatedTarget.closest('[data-fb-title]') === evenement.target.closest('[data-fb-title]')) {
                return;
            }

            clearTimeout(this.minuterie);
            this.ouvert = false;
        },
    }));
});
