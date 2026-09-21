import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { glob } from 'glob';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // One space, one pair of entries. app.css and app.js carry what
                // both share and are not entries themselves: web and admin
                // import them, each into its own compilation.
                'resources/css/web.css',
                'resources/js/web.js',
                'resources/css/admin.css',
                'resources/js/admin.js',

                // The per-page CSS and JS, loaded by a `@vite` in their own
                // view. They never carry Tailwind: only our rules, whose
                // selectors are ours alone. A directory holding an `index` is
                // a page, and its path mirrors the path of the view it dresses.
                //
                // The pattern is recursive on purpose: a single level would not
                // see `web/prestations/{slug}/index.css` the day it exists, the
                // page would render without its style, and nothing would say so.
                ...glob.sync('resources/css/**/index.css'),
                ...glob.sync('resources/js/**/index.js'),
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
