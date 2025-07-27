import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import vueJsx from '@vitejs/plugin-vue-jsx';
import obfuscatorPlugin from 'vite-plugin-javascript-obfuscator';
import { visualizer } from 'rollup-plugin-visualizer';
import fs from 'fs';
import path from 'path';

// Archivos CSS globales
const cssFiles = [
    'resources/css/rke/styles.css',
    'resources/css/rke/auth-pages.css',
    'resources/css/games/bingo/bingo-game-rooms.css',
    'resources/css/games/bingo/bingo-in-game.css',
    'resources/css/games/bingo/bingo-handle-patterns.css',
];

// Archivos JS globales
const globalJsFiles = [
    'resources/js/app.js',
    'resources/js/helpers/toast-notify.ts',
    'resources/js/helpers/register.js',
    'resources/js/helpers/crypt.js',
    'resources/js/helpers/get-uuid.js',
    'resources/js/helpers/functions.ts',
    'resources/js/helpers/time-functions.js',
    'resources/js/helpers/format-daQte-time-unix.js',
    'resources/js/helpers/switch-payment-method.js',
    'resources/js/helpers/payment-confirm-button.js',
];

// Función para detectar bundles helpers y main por juego
function getGameBundles() {
    const gamesPath = path.resolve(__dirname, 'resources/js/games');
    const helpers: string[] = [];
    const mains: string[] = [];

    if (fs.existsSync(gamesPath)) {
        const games = fs.readdirSync(gamesPath, { withFileTypes: true })
            .filter(dirent => dirent.isDirectory())
            .map(dirent => dirent.name);

        for (const game of games) {
            const basePath = path.join(gamesPath, game);

            // Helper (TS o JS)
            const helperTs = path.join(basePath, `${game}.ts`);
            const helperJs = path.join(basePath, `${game}.js`);
            if (fs.existsSync(helperTs)) helpers.push(`resources/js/games/${game}/${game}.ts`);
            else if (fs.existsSync(helperJs)) helpers.push(`resources/js/games/${game}/${game}.js`);

            // Main (TS o JS)
            const mainTs = path.join(basePath, `${game}-main.ts`);
            const mainJs = path.join(basePath, `${game}-main.js`);
            if (fs.existsSync(mainTs)) mains.push(`resources/js/games/${game}/${game}-main.ts`);
            else if (fs.existsSync(mainJs)) mains.push(`resources/js/games/${game}/${game}-main.js`);
        }
    }

    return { helpers, mains };
}

const { helpers: gameHelpers, mains: gameMains } = getGameBundles();

const inputs = [...cssFiles, ...globalJsFiles, ...gameHelpers, ...gameMains];

export default defineConfig(({ mode }) => {
    const isProduction = mode === 'production';

    return {
        plugins: [
            laravel({
                input: inputs,
                refresh: true,
            }),
            vue(),
            vueJsx(),
            ...(isProduction
                ? [
                    obfuscatorPlugin({
                        include: ['resources/js/**/*.ts', 'resources/js/**/*.tsx', 'resources/js/**/*.js'],
                        exclude: [/node_modules/],
                        apply: 'build',
                        options: {
                            identifierNamesGenerator: 'mangled',
                            reservedStrings: ['route', 'Ziggy'],
                            compact: true,
                            debugProtection: true,
                            selfDefending: true,
                        },
                    }),
                    visualizer({
                        filename: './build-stats.html',
                        open: true,
                        gzipSize: true,
                        brotliSize: true,
                    }),
                ]
                : []),
        ],
        build: {
            sourcemap: !isProduction,
            minify: isProduction,
            rollupOptions: {
                output: {
                    manualChunks(id) {
                        if (id.includes('resources/js/games/')) {
                            const match = id.match(/resources\/js\/games\/([^\/]+)\//);
                            if (match) {
                                const game = match[1];
                                if (id.includes('/components/')) return `${game}-components`;
                                return game;
                            }
                        }
                        if (id.includes('resources/js/rke')) return 'rke';
                    },
                },
            },
        },
        resolve: {
            alias: {
                '@': path.resolve(__dirname, 'resources/js'),
                vue: 'vue/dist/vue.esm-bundler.js',
            },
        },
        server: {
            host: '127.0.0.1',
            port: 5173,
            strictPort: true,
        },
    };
});