import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                // Telegram Mini App (Vue + Inertia)
                'resources/css/miniapp.css',
                'resources/js/miniapp/app.js',
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', { weights: [400, 500, 600] }),
                bunny('Space Grotesk', { weights: [500, 600, 700] }),
                bunny('DM Sans', { weights: [400, 500, 600, 700] }),
            ],
        }),
        vue(),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
