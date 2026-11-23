import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.tsx'],
            refresh: true,
            fonts: [bunny('Instrument Sans', { weights: [400, 500, 600] })],
        }),
        react(),
        tailwindcss(),
    ],
    server: {
        host: '0.0.0.0',
        hmr: { host: 'localhost' },
        watch: { ignored: ['**/storage/framework/views/**'] },
    },
});
