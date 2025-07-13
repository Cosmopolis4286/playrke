import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import vueJsx from '@vitejs/plugin-vue-jsx'
import obfuscatorPlugin from 'vite-plugin-javascript-obfuscator'
import { visualizer } from 'rollup-plugin-visualizer'
import fs from 'fs'
import path from 'path'

// Archivos CSS globales
const cssFiles = [
    'resources/css/rke/styles.css',
    'resources/css/rke/auth-pages.css',
    'resources/css/games/bingo/bingo-game-rooms.css',
    'resources/css/games/bingo/bingo-in-game.css',
    'resources/css/games/bingo/bingo-handle-patterns.css',
]

// Archivos JS globales
const globalJsFiles = [
    'resources/js/app.js',
    'resources/js/rke/toast-notify.js',
    'resources/js/rke/register.js',
    'resources/js/rke/rke-crypt.js',
    'resources/js/rke/get-uuid.js',
    'resources/js/rke/rke-functions.js',
    'resources/js/rke/time-functions.js',
    'resources/js/rke/format-daQte-time-unix.js',
    'resources/js/rke/switch-payment-method.js',
    'resources/js/rke/payment-confirm-button.js',
]

// Función para detectar bundles helpers y main por juego
function getGameBundles() {
    const gamesPath = path.resolve(__dirname, 'resources/js/games')
    const helpers = []
    const mains = []

    if (fs.existsSync(gamesPath)) {
        const games = fs
            .readdirSync(gamesPath, { withFileTypes: true })
            .filter((dirent) => dirent.isDirectory())
            .map((dirent) => dirent.name)

        for (const game of games) {
            const basePath = path.join(gamesPath, game)

            const helperFile = path.join(basePath, `${game}.js`)
            if (fs.existsSync(helperFile)) helpers.push(`resources/js/games/${game}/${game}.js`)

            const mainFile = path.join(basePath, `${game}-main.js`)
            if (fs.existsSync(mainFile)) mains.push(`resources/js/games/${game}/${game}-main.js`)
        }
    }

    return { helpers, mains }
}

const { helpers: gameHelpers, mains: gameMains } = getGameBundles()

const inputs = [...cssFiles, ...globalJsFiles, ...gameHelpers, ...gameMains]

export default defineConfig(({ mode }) => {
    const isProduction = mode === 'production'

    return {
        plugins: [
            laravel({
                input: inputs,
                refresh: true,
            }),
            vue(),
            vueJsx(),
            // Plugins solo para producción
            ...(isProduction
                ? [
                    obfuscatorPlugin({
                        include: ['resources/js/**/*.js', 'resources/js/**/*.tsx'],
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
                            const match = id.match(/resources\/js\/games\/([^\/]+)\//)
                            if (match) {
                                const game = match[1]
                                if (id.includes('/components/')) return `${game}-components`
                                return game
                            }
                        }
                        if (id.includes('resources/js/rke')) return 'rke'
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
    }
})
