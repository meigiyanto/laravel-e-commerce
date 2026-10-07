import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/storefront.css',
                'resources/css/dashboard.css',

                'resources/js/app.js',
                'resources/js/storefront.js',
                'resources/js/dashboard.js',
            ],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            '@fonts': path.resolve(import.meta.dirname, 'resources/fonts'),
        },
    },
    build: {
        cssMinify: false,
        minify: false,
    },
    server: {
        watch: {
            usePolling: true,
        }
    },
});
