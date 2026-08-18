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
 * Le repli de la barre laterale sur grand ecran.
 *
 * L'etat vit sur `<html>`, pose avant la peinture par le script du layout, et
 * les regles de `ui-kit.css` en tirent la largeur de la barre et du contenu.
 * Retenu comme le theme : on ne redemande pas a chaque page ce qui a ete
 * decide une fois.
 *
 * Le bouton n'existe qu'au-dela de `wide`, ou la barre est ouverte d'office :
 * en dessous elle est deja un rail, et il n'y aurait rien a replier.
 */
window.basculerLaBarre = function () {
    const repliee = document.documentElement.dataset.fbBarre === 'repliee';

    if (repliee) {
        delete document.documentElement.dataset.fbBarre;
    } else {
        document.documentElement.dataset.fbBarre = 'repliee';
    }

    try {
        localStorage.setItem('fb-barre', repliee ? 'ouverte' : 'repliee');
    } catch (e) {
        /* Navigation privee : l'etat vaut pour la page, et c'est deja ca. */
    }
};
