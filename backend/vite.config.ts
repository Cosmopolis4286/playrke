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
      (file.name.endsWith('.js') || file.name.endsWith('.ts') || file.name.endsWith('.vue'))
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
      ...(isProduction
        ? [
            // Ofuscador para proteger todos los JS y TS en resources/js (recursivamente)
            obfuscatorPlugin({
              include: ['resources/js/**/*.ts', 'resources/js/**/*.js'],
              exclude: [/node_modules/],
              apply: 'build',
              options: {
                // Ofuscación de identificadores
                identifierNamesGenerator: 'mangled-shuffled',
                reservedNames: ['^$', '_', 'console', 'window', 'document'], // Proteger símbolos críticos
                reservedStrings: ['route', 'Ziggy', 'csrf', 'api'], // Palabras clave de la app
                
                // Protección contra debugging
                debugProtection: true,
                debugProtectionInterval: 2000, // Chequeo cada 2 segundos
                selfDefending: true,
                
                // Ofuscación de strings
                stringArray: true,
                stringArrayEncoding: ['rc4', 'base64'],
                stringArrayThreshold: 0.75, // 75% de strings ofuscados
                
                // Protección adicional
                deadCodeInjection: true,
                deadCodeInjectionThreshold: 0.4, // 40% código muerto
                controlFlowFlattening: true,
                controlFlowFlatteningThreshold: 0.75,
                
                // Compactación
                compact: true,
                simplify: true,
                
                // Deshabilitar funciones de desarrollo
                disableConsoleOutput: true, // Elimina console.*
                
                // Transformaciones
                numbersToExpressions: true,
                transformObjectKeys: true,
                shuffleStringArray: true,
                rotateStringArray: true,
                
                // Opciones de dominio (opcional - descomenta si quieres bloquear dominios)
                // domainLock: ['playrke.com', 'www.playrke.com'],
              }
            }),
            // Visualizador del bundle para análisis (abrir después de build)
            visualizer({
              filename: './build-stats.html',
              open: true,
              gzipSize: true,
              brotliSize: true
            })
          ]
        : [])
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
      host: '127.0.0.1',
      port: 5173,
      strictPort: true,
      hmr: {
        host: 'localhost'
      }
    }
  }
})
