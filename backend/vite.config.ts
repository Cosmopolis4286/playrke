import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import vueJsx from '@vitejs/plugin-vue-jsx'
import fs from 'fs'
import path from 'path'

import obfuscatorPluginRaw from 'vite-plugin-javascript-obfuscator'
const obfuscatorPlugin = obfuscatorPluginRaw as unknown as (options: any) => any
import { visualizer } from 'rollup-plugin-visualizer'

// ---------------------------------------------
// Archivos CSS globales que siempre se incluyen
// ---------------------------------------------
const cssFiles = [
  'resources/css/global/styles.css',
  'resources/css/global/loader.css',
]

// ---------------------------------------------
// Función recursiva para recoger todos .ts, .js y .vue en resources/js y subcarpetas
// ---------------------------------------------
function getAllJsTsFiles(dir: string, baseDir: string = dir): string[] {
  let results: string[] = []

  if (!fs.existsSync(dir)) return results

  const list = fs.readdirSync(dir, { withFileTypes: true })

  for (const file of list) {
    const fullPath = path.join(dir, file.name)

    if (file.isDirectory()) {
      results = results.concat(getAllJsTsFiles(fullPath, baseDir))
    } else if (
      file.isFile() &&
      (file.name.endsWith('.js') || file.name.endsWith('.ts') || file.name.endsWith('.vue')) &&
      !file.name.endsWith('.test.ts') && !file.name.endsWith('.test.js') &&
      !file.name.endsWith('.spec.ts') && !file.name.endsWith('.spec.js')
    ) {
      // Convertir a ruta relativa desde baseDir y con separadores Unix
      const relativePath = path.relative(baseDir, fullPath).split(path.sep).join('/')
      const fullRelativePath = `resources/js/${relativePath}`

      results.push(fullRelativePath)
    }
  }

  return results
}

// ---------------------------------------------
// Recoge todos los archivos JS/TS/Vue bajo resources/js
// ---------------------------------------------
const allJsTsFiles = getAllJsTsFiles(path.resolve(__dirname, 'resources/js'))

// ---------------------------------------------
// Inputs completos para Vite: CSS + todos JS/TS/Vue
// ---------------------------------------------
const inputs = [...cssFiles, ...allJsTsFiles]

// ---------------------------------------------
// Exportar configuración Vite
// ---------------------------------------------
export default defineConfig(({ mode }) => {
  const isProduction = mode === 'production'

  return {
    plugins: [
      laravel({
        input: inputs,
        refresh: true // HMR para Blade y Vue
      }),
      vue(), // Plugin Vue 3
      vueJsx(), // Soporte JSX en Vue
      // La ofuscación se deja opt-in: el plugin actual reescribe declaraciones
      // const y provoca advertencias masivas de Rollup en módulos Vue/TypeScript.
      // La minificación de Vite sigue activa para todas las compilaciones productivas.
      ...(isProduction && process.env.VITE_ENABLE_OBFUSCATION === 'true'
        ? [
            obfuscatorPlugin({
              include: ['resources/js/**/*.ts', 'resources/js/**/*.js'],
              exclude: [/node_modules/],
              apply: 'build',
              options: {
                identifierNamesGenerator: 'mangled-shuffled',
                reservedNames: ['^$', '_', 'console', 'window', 'document'],
                reservedStrings: ['route', 'Ziggy', 'csrf', 'api'],
                debugProtection: true,
                debugProtectionInterval: 2000,
                selfDefending: true,
                stringArray: true,
                stringArrayEncoding: ['rc4', 'base64'],
                stringArrayThreshold: 0.75,
                deadCodeInjection: true,
                deadCodeInjectionThreshold: 0.4,
                controlFlowFlattening: true,
                controlFlowFlatteningThreshold: 0.75,
                compact: true,
                simplify: true,
                disableConsoleOutput: true,
                numbersToExpressions: true,
                transformObjectKeys: true,
                shuffleStringArray: true,
                rotateStringArray: true,
              },
            }),
          ]
        : []),
      // El informe de bundle solo se genera bajo demanda para no incluir
      // build-stats.html accidentalmente en la imagen de producción.
      ...(isProduction && process.env.VITE_ANALYZE_BUNDLE === 'true'
        ? [
            visualizer({
              filename: './build-stats.html',
              open: false,
              gzipSize: true,
              brotliSize: true,
            }),
          ]
        : []),
    ],

    build: {
      sourcemap: !isProduction, // Sourcemap solo en dev
      minify: isProduction, // Minificar solo en producción
      rollupOptions: {
        output: {
          // División de chunks por juego y carpeta 'rke'
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
          }
        }
      }
    },

    resolve: {
      alias: {
        '@': path.resolve(__dirname, 'resources'), // alias para resources
        '@js': path.resolve(__dirname, 'resources/js'), // alias para resources/js
        vue: 'vue/dist/vue.esm-bundler.js' // Si usas Vue 3, esta versión está bien
      }
    },

    server: {
      host: '0.0.0.0',
      port: 5173,
      strictPort: true,
      hmr: {
        host: 'localhost',
        port: 5173,
      },
      cors: true,
      allowedHosts: ['localhost', '127.0.0.1', 'host.docker.internal', '.localhost'],
    }
  }
})
