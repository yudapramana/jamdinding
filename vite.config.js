import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
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
                }
            }
        })
    ],
    resolve: {
        dedupe: ['jquery'],                // cegah duplikasi instance
        alias: { jquery: 'jquery/dist/jquery.js' },
        // alias: {
            //   "@": "/resources/assets/js",
            // use vue's runtime compiler to support vue components
            // directly within blade templates
            //   vue: "vue/dist/vue.esm-bundler.js",
        // },
    },
    //   envPrefix: ["VITE_", "MIX_"],
});
