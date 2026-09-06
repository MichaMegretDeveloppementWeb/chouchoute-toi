import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { glob } from 'glob';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // Un espace, une paire d'entrées. `app.css` et `app.js`
                // portent le commun aux deux et ne sont pas des entrées :
                // `web` et `admin` les importent, chacun dans sa compilation.
                // Deux feuilles Tailwind sur une page écrivent les mêmes noms
                // de classes, et la dernière chargée gagne par sa position.
                'resources/css/web.css',
                'resources/js/web.js',
                'resources/css/admin.css',
                'resources/js/admin.js',

                // Le CSS et le JS par page du site public, chargés par un
                // `@vite` dans leur vue. Ils ne portent jamais Tailwind : que
                // des règles à nous, dont les sélecteurs n'appartiennent qu'à
                // nous.
                ...glob.sync('resources/css/web/*/index.css'),
                ...glob.sync('resources/js/web/*/index.js'),
                'resources/css/components/layout/header.css',
                'resources/css/components/layout/footer.css',
                'resources/js/components/layout/header.js',
                'resources/js/components/layout/footer.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
