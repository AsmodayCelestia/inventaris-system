import { defineConfig } from 'vite'; // ← ini WAJIB ADA
import { loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin'; // ← WAJIB ADA
import vue from '@vitejs/plugin-vue';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd());

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
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
                vue: 'vue/dist/vue.esm-bundler.js',
            },
        },
        server: {
            host: '0.0.0.0',
            port: 5173,
            allowedHosts: 'all',
            hmr: {
                host: env.VITE_DEV_SERVER_HOST,
                protocol: env.VITE_USE_WSS === 'true' ? 'wss' : 'ws',
            },
        },
        base: env.ASSET_URL ? new URL(env.ASSET_URL).origin + '/' : '/',
    };
});
