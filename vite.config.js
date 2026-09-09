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

                // The public site's per-page CSS and JS, loaded by a `@vite` in
                // their own view. They never carry Tailwind: only our rules,
                // whose selectors are ours alone. A directory under `pages/`
                // is a page, and nothing else lives there.
                ...glob.sync('resources/css/pages/*/index.css'),
                ...glob.sync('resources/js/pages/*/index.js'),
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
