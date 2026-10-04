import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/admin.css',
                'resources/js/admin.js'
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
    }
});
