/*
 * LE script du site public.
 *
 * **Cette entree importe, elle ne contient pas.** Nos scripts par page vivent
 * sous `web/{page}/index.js`, charges par `@vite` dans leur vue · ce fichier ne
 * porte que ce qui vaut pour tout le site.
 *
 * Le collecteur de falcon/analytics est ici, et non dans `admin.js` · on ne
 * mesure pas les visites de l'administratrice.
 */
import '../../vendor/falcon/analytics/resources/js/collector.js';

import './app.js';
