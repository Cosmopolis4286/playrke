import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import obfuscatorPlugin from 'vite-plugin-javascript-obfuscator';

// Lista de todos los archivos JS que se van a incluir y posiblemente ofuscar
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
    'resources/js/games/bingo/in-game.js',
    'resources/js/games/bingo/includes/adjust-height-boxes.js',
    'resources/js/games/bingo/includes/auto-marked.js',
    'resources/js/games/bingo/includes/bingo-out-buy-ctns.js',
    'resources/js/games/bingo/includes/handle-bingo-plays.js',
    'resources/js/games/bingo/includes/prize-ctns-pending-payments.js',
    'resources/js/games/bingo/tests/regenerate-draw.js'
];

// Lista de CSS (solo para laravel plugin)
const cssFiles = [
    'resources/css/rke/styles.css',
    'resources/css/rke/home-page.css',
    'resources/css/games/bingo/bingo-game-rooms.css',
    'resources/css/games/bingo/bingo-in-game.css',
    'resources/css/games/bingo/bingo-handle-plays.css',
];

export default defineConfig(({ mode }) => {
    const isProduction = mode === 'production';

    return {
        plugins: [
            laravel({
                input: [...cssFiles, ...jsFiles],
                refresh: true,
            }),
            obfuscatorPlugin({
                include: jsFiles,
                exclude: [/node_modules/],
                apply: 'build',
                options: {
                    identifierNamesGenerator: 'mangled', // Evita que se rompan los nombres como 'route'
                    reservedStrings: ['route', 'Ziggy'], // Protege las referencias globales también
                    compact: true,
                    debugProtection: isProduction,
                    selfDefending: isProduction,
                },
            }),
        ],
    };
});