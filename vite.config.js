// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
    server: {
        // Configure for your localhost setup
        host: 'localhost',
        port: 5173, // Default Vite port
        hmr: {
            host: 'localhost',
        },
    },
    // Add this if you have any CORS issues
    define: {
        global: 'globalThis',
    },
});