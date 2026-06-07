import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/designer.js',
                'resources/js/booking.js',
            ],
            refresh: true,
        }),
    ],
});
