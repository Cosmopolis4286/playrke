import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import obfuscatorPlugin from 'vite-plugin-javascript-obfuscator';

export default defineConfig(({ mode }) => {
    const isProduction = mode === 'production';

    return {
        plugins: [
            laravel({
                input: [
                    'resources/css/rke/styles.css',
                    'resources/css/rke/auth-pages.css',
                    'resources/css/games/bingo/bingo-game-rooms.css',
                    'resources/css/games/bingo/bingo-in-game.css',
                    'resources/css/games/bingo/bingo-handle-plays.css',
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
                    'resources/js/games/bingo/includes/auto-marked.js',
                    'resources/js/games/bingo/includes/bingo-out-buy-ctns.js'
                ],
                refresh: true,
            }),
            obfuscatorPlugin({
                include: [
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
                    'resources/js/games/bingo/includes/auto-marked.js',
                    'resources/js/games/bingo/includes/bingo-out-buy-ctns.js'
                ],
                exclude: [/node_modules/],
                apply: "build",
                debugger: isProduction,
                options: {
                    debugProtection: isProduction,
                },
            }),
        ],
    };
});