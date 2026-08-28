import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { glob } from 'glob';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                ...glob.sync('resources/css/web/*/index.css'),
                ...glob.sync('resources/js/web/*/index.js'),
                'resources/css/components/layout/header.css',
                'resources/css/components/layout/footer.css',
                'resources/js/components/layout/header.js',
                'resources/js/components/layout/footer.js',
                'resources/css/ui-kit.css',
                'resources/js/ui-kit.js',
                // Écrans de falcon/booking : liste triable et menus d'actions.
                'packages/falcon-booking/resources/js/booking-admin.js',
                // Sa page publique, dont il possède le style de bout en bout.
                'packages/falcon-booking/resources/css/booking-public.css',
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
