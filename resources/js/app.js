/*
 * Ce qui est commun aux deux espaces · le site public et le back-office.
 *
 * **Ce fichier n'est pas une entree.** Il n'est pas declare dans
 * `vite.config.js` · ce sont `web.js` et `admin.js` qui l'importent.
 *
 * Il est vide aujourd'hui, et il reste · le jour ou un module vaut pour les
 * deux espaces, il a sa place, et les deux entrees le prennent sans qu'on y
 * touche.
 *
 * Il a porte `import './bootstrap'`, qui posait axios sur `window`. Ni l'un ni
 * l'autre n'etaient utilises nulle part, et ce fichier n'etait meme pas une
 * entree de Vite · `welcome.blade.php` le demandait par `@vite`, ce qui aurait
 * leve « Unable to locate file in Vite manifest » si la page s'etait affichee.
 * Les trois sont partis le 2026-09-06.
 */
