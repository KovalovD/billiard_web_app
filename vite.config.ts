import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
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
    server: {
        host: '0.0.0.0',
        port: 8001,
        hmr: {
            host: 'localhost'
        },
        // Use environment variables for URLs
        origin: process.env.VITE_APP_URL || 'http://localhost:8001'
    },
    build: {
        // Don't hardcode URLs in build
        rollupOptions: {
            output: {
                manualChunks: undefined,
            }
        }
    },
    define: {
        // Make environment variables available to frontend
        __APP_URL__: JSON.stringify(process.env.VITE_APP_URL || process.env.APP_URL || 'http://localhost:8001'),
        __API_URL__: JSON.stringify(process.env.VITE_API_URL || process.env.APP_URL || 'http://localhost:8001'),
    },
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
});
