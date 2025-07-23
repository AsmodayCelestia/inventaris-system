import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue'; // <-- PASTIKAN INI DI-IMPORT

export default defineConfig({
    plugins: [
        laravel({ // <-- PASTIKAN PLUGIN LARAVEL INI ADA
            input: 'resources/js/app.js', // Pastikan ini menunjuk ke entry point Vue kamu
            refresh: true,
        }),
        vue({ // <-- PASTIKAN PLUGIN VUE INI ADA
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        // Jika kamu ingin menggunakan @tailwindcss/postcss, tambahkan di sini setelah plugin Vue
        // Tapi biasanya sudah dihandle oleh laravel-vite-plugin atau postcss.config.js
        // Jangan langsung di sini seperti yang kamu punya sebelumnya.
    ],
    resolve: { // <-- PASTIKAN BAGIAN resolve INI ADA UNTUK FIX RUNTIME COMPILER
        alias: {
            'vue': 'vue/dist/vue.esm-bundler.js'
        }
    }
});
