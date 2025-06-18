import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import obfuscatorPlugin from 'vite-plugin-javascript-obfuscator';

// Archivos JS (incluye app.js primero)
const jsFiles = [
    'resources/js/app.js',
    'resources/js/rke/toast-notify.js',
    'resources/js/rke/register.js',
    'resources/js/rke/rke-crypt.js',
    'resources/js/rke/get-uuid.js',
    'resources/js/rke/rke-functions.js',
    'resources/js/rke/time-functions.js',
    'resources/js/rke/format-date-time-unix.js',
    'resources/js/rke/switch-payment-method.js',
    'resources/js/rke/payment-confirm-button.js',
    'resources/js/games/bingo/form.js',
    'resources/js/games/bingo/ctn-price-coins.js',
    'resources/js/games/bingo/rounds-config.js',
    'resources/js/games/bingo/in-game.js',
    'resources/js/games/bingo/bingo-in-game.tsx',
    'resources/js/games/bingo/includes/adjust-height-boxes.js',
    'resources/js/games/bingo/includes/auto-marked.js',
    'resources/js/games/bingo/includes/bingo-out-buy-ctns.js',
    'resources/js/games/bingo/includes/handle-bingo-patterns.js',
    'resources/js/games/bingo/includes/prize-ctns-pending-payments.js',
    'resources/js/games/bingo/tests/regenerate-draw.js'
];

// Archivos CSS
const cssFiles = [
    'resources/css/rke/styles.css',
    'resources/css/rke/auth-pages.css',
    'resources/css/games/bingo/bingo-game-rooms.css',
    'resources/css/games/bingo/bingo-in-game.css',
    'resources/css/games/bingo/bingo-handle-patterns.css',
];

export default defineConfig(({ mode }) => {
    const isProduction = mode === 'production';

    return {
        plugins: [
            laravel({
                input: [...cssFiles, ...jsFiles],
                refresh: true,
            }),
            vue(),
            obfuscatorPlugin({
                include: jsFiles,
                exclude: [/node_modules/],
                apply: 'build',
                options: {
                    identifierNamesGenerator: 'mangled',
                    reservedStrings: ['route', 'Ziggy'],
                    compact: true,
                    debugProtection: isProduction,
                    selfDefending: isProduction,
                },
            }),
        ],
        build: {
            // Opcional: puedes ajustar más opciones aquí
            sourcemap: !isProduction,
            minify: isProduction,
            rollupOptions: {
                // Opcional: configura split chunks o alias aquí si quieres
            },
        },
        resolve: {
            alias: {
                // Alias para Vue 3 runtime completo si usas templates en .vue
                'vue': 'vue/dist/vue.esm-bundler.js',
            },
        },
    };
});
