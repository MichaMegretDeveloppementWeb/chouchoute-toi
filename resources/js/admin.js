/*
 * LE script du back-office.
 *
 * **Cette entree importe, elle ne contient pas.** Notre code vit dans `admin/`.
 *
 * Les paquets livrent leurs scripts compiles et autonomes · aucun `import` vers
 * un paquet npm, donc rien a installer de notre cote. C'est notre build qui les
 * nomme, les versionne et les sert, comme n'importe quel module a nous.
 *
 * Chart.js n'est pas ici · le script du kit le demande par un import dynamique,
 * et Vite en fait un fichier a part, charge seulement quand un graphique parait.
 */
import '../../vendor/falcon/ui-kit/dist/ui-kit.js';
import '../../vendor/falcon/booking/dist/booking-admin.js';

import './app.js';
import './admin/sidebar.js';
