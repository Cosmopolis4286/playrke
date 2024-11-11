import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import obfuscatorPlugin from 'vite-plugin-javascript-obfuscator'; 

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/rke/styles.css',
                'resources/css/rke/auth-pages.css',
                'resources/css/games/bingo/bingo-game-rooms.css',
                'resources/css/games/bingo/bingo-in-game.css',
                //'resources/js/app.js',
                'resources/js/rke/toast-notify.js',
                'resources/js/rke/register.js',
                'resources/js/rke/rke-crypt.js',
                'resources/js/rke/rke-functions.js',
                'resources/js/rke/time-functions.js',
                'resources/js/rke/format-date-time-unix.js',
                'resources/js/rke/switch-payment-method.js',
                'resources/js/rke/payment-confirm-button.js',
                'resources/js/games/bingo/in-game.js',
                'resources/js/games/bingo/auto-marked.js',
                'resources/js/games/bingo/includes/bingo-out-buy-ctns.js'
            ],
            refresh: true,
        }),
        obfuscatorPlugin({
            include: [
                //'resources/js/app.js',
                'resources/js/rke/toast-notify.js',
                'resources/js/rke/register.js',
                'resources/js/rke/rke-crypt.js',
                'resources/js/rke/rke-functions.js',
                'resources/js/rke/time-functions.js',
                'resources/js/rke/format-date-time-unix.js',
                'resources/js/rke/switch-payment-method.js',
                'resources/js/rke/payment-confirm-button.js',
                'resources/js/games/bingo/in-game.js',
                'resources/js/games/bingo/auto-marked.js',
                'resources/js/games/bingo/includes/bingo-out-buy-ctns.js'
            ],
            exclude: [/node_modules/],
            apply: "build", // Aplica solo en el build de producción
            debugger: false, // Habilita protección de depuración
            options: {
                debugProtection: false, // Opciones adicionales para proteger el código
                // Agrega más opciones si es necesario
            },
        }),
    ],
});
