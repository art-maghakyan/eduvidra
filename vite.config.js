import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    server: {
        host: '0.0.0.0', // Listen on all network interfaces
        hmr: {
            host: 'localhost', // The browser should look for updates on your local machine
        },
        watch: {
            usePolling: true, // Crucial for Docker on some filesystems to detect changes
        },
    },
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
            '@img': '/resources/images', 
        },
    },
});
