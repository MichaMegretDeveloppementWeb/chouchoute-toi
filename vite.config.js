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
                // whose selectors are ours alone.
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
