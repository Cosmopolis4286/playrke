import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import vueJsx from '@vitejs/plugin-vue-jsx';
import fs from 'fs';
import path from 'path';

import obfuscatorPluginRaw from 'vite-plugin-javascript-obfuscator';
const obfuscatorPlugin = obfuscatorPluginRaw as unknown as (options: any) => any; import { visualizer } from 'rollup-plugin-visualizer';


// ---------------------------------------------
// Archivos CSS globales que siempre se incluyen
// ---------------------------------------------
const cssFiles = [
    'resources/css/rke/styles.css',
];

// ---------------------------------------------
// Archivos JS/TS globales que siempre se incluyen
// ---------------------------------------------
const globalJsFiles = [
    'resources/js/app.js',
    'resources/js/helpers/global.ts',
    'resources/js/helpers/loader.ts',
];

// ---------------------------------------------
// Detecta helpers y mains dinámicamente por juego
// ---------------------------------------------
function getGameBundles() {
    const gamesPath = path.resolve(__dirname, 'resources/js/games');
    const helpers: string[] = [];
    const mains: string[] = [];

    if (!fs.existsSync(gamesPath)) return { helpers, mains };

    const games = fs.readdirSync(gamesPath, { withFileTypes: true })
        .filter(dirent => dirent.isDirectory())
        .map(dirent => dirent.name);

    for (const game of games) {
        const basePath = path.join(gamesPath, game);

        // Helper TS o JS
        const helperTs = path.join(basePath, `${game}.ts`);
        const helperJs = path.join(basePath, `${game}.js`);
        if (fs.existsSync(helperTs)) helpers.push(`resources/js/games/${game}/${game}.ts`);
        else if (fs.existsSync(helperJs)) helpers.push(`resources/js/games/${game}/${game}.js`);

        // Main TS o JS
        const mainTs = path.join(basePath, `${game}-main.ts`);
        const mainJs = path.join(basePath, `${game}-main.js`);
        if (fs.existsSync(mainTs)) mains.push(`resources/js/games/${game}/${game}-main.ts`);
        else if (fs.existsSync(mainJs)) mains.push(`resources/js/games/${game}/${game}-main.js`);
    }

    return { helpers, mains };
}

const { helpers: gameHelpers, mains: gameMains } = getGameBundles();

// Inputs completos para Vite
const inputs = [...cssFiles, ...globalJsFiles, ...gameHelpers, ...gameMains];

// ---------------------------------------------
// Exportar configuración Vite
// ---------------------------------------------
export default defineConfig(({ mode }) => {
    const isProduction = mode === 'production';

    return {
        plugins: [
            laravel({
                input: inputs,
                refresh: true, // HMR para Blade y Vue
            }),
            vue(),    // Plugin Vue 3
            vueJsx(), // Soporte JSX en Vue
            ...(isProduction
                ? [
                    // Ofuscador para proteger JS en producción
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
                    // Visualizador del bundle para análisis
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
            sourcemap: !isProduction, // Sourcemap solo en dev
            minify: isProduction,     // Minificar solo en producción
            rollupOptions: {
                output: {
                    // División de chunks por juego y carpeta 'rke'
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
