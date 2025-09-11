import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import vueJsx from '@vitejs/plugin-vue-jsx';
import fs from 'fs';
import path from 'path';
import { visualizer } from 'rollup-plugin-visualizer';

// ---------------------------------------------
// Archivos CSS globales que siempre se incluyen
// ---------------------------------------------
const cssFiles = [
  'frontend/assets/styles/design-system.css',
  'frontend/assets/animations/transitions.css',
  'resources/css/shared/styles.css'
];

// ---------------------------------------------
// Función recursiva para recoger todos .ts y .js en frontend y resources/js
// ---------------------------------------------
function getAllJsTsFiles(dir: string, baseDir: string = dir): string[] {
  let results: string[] = [];

  if (!fs.existsSync(dir)) return results;

  const list = fs.readdirSync(dir, { withFileTypes: true });

  for (const file of list) {
    const fullPath = path.join(dir, file.name);

    if (file.isDirectory()) {
      results = results.concat(getAllJsTsFiles(fullPath, baseDir));
    } else if (file.isFile() && (file.name.endsWith('.js') || file.name.endsWith('.ts'))) {
      // Convertir a ruta relativa desde baseDir y con separadores Unix
      const relativePath = path.relative(baseDir, fullPath).split(path.sep).join('/');
      
      // Ajustar la ruta según el directorio base
      if (baseDir.includes('frontend')) {
        results.push(`frontend/${relativePath}`);
      } else {
        results.push(`resources/js/${relativePath}`);
      }
    }
  }

  return results;
}

// ---------------------------------------------
// Recoge todos los archivos JS/TS
// ---------------------------------------------
const frontendFiles = getAllJsTsFiles(path.resolve(__dirname, 'frontend'));
const resourcesFiles = getAllJsTsFiles(path.resolve(__dirname, 'resources/js'));

// Punto de entrada principal del frontend
const mainEntry = 'frontend/app.js';

// Inputs completos para Vite
const inputs = [
  mainEntry,
  ...cssFiles,
  ...frontendFiles.filter(f => f !== mainEntry),
  ...resourcesFiles
];

// ---------------------------------------------
// Exportar configuración Vite optimizada
// ---------------------------------------------
export default defineConfig(({ mode }) => {
  const isProduction = mode === 'production';

  return {
    plugins: [
      laravel({
        input: inputs,
        refresh: [
          'frontend/**/*.vue',
          'frontend/**/*.js',
          'frontend/**/*.ts',
          'resources/views/**/*.blade.php'
        ],
      }),
      vue({
        template: {
          transformAssetUrls: {
            base: null,
            includeAbsolute: false,
          },
        },
      }),
      vueJsx(),
      ...(isProduction ? [
        // Visualizador del bundle para análisis
        visualizer({
          filename: './build-stats.html',
          open: false,
          gzipSize: true,
          brotliSize: true,
        }),
      ] : []),
    ],

    build: {
      sourcemap: !isProduction,
      minify: isProduction,
      target: 'es2020',
      rollupOptions: {
        output: {
          // División de chunks optimizada
          manualChunks(id) {
            // Vendor chunks
            if (id.includes('node_modules')) {
              if (id.includes('vue') || id.includes('@vue')) return 'vue-vendor';
              if (id.includes('@inertiajs')) return 'inertia-vendor';
              if (id.includes('@headlessui') || id.includes('@heroicons')) return 'ui-vendor';
              if (id.includes('tailwindcss')) return 'css-vendor';
              return 'vendor';
            }
            
            // Frontend chunks
            if (id.includes('frontend/')) {
              if (id.includes('components/ui/')) return 'ui-components';
              if (id.includes('components/dashboard/')) return 'dashboard-components';
              if (id.includes('components/layout/')) return 'layout-components';
              if (id.includes('layouts/')) return 'layouts';
              if (id.includes('pages/')) return 'pages';
              if (id.includes('composables/')) return 'composables';
              return 'frontend';
            }

            // Legacy resources (para migración gradual)
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
          
          // Optimizaciones de assets
          assetFileNames: (assetInfo) => {
            const info = assetInfo.name!.split('.');
            const ext = info[info.length - 1];
            
            if (/png|jpe?g|svg|gif|tiff|bmp|ico/i.test(ext)) {
              return 'assets/images/[name]-[hash][extname]';
            }
            if (/css/i.test(ext)) {
              return 'assets/css/[name]-[hash][extname]';
            }
            return 'assets/[name]-[hash][extname]';
          },
          
          chunkFileNames: 'assets/js/[name]-[hash].js',
          entryFileNames: 'assets/js/[name]-[hash].js',
        },
        
        // Optimizaciones externas
        external: (id) => {
          // No externalizar nada en build, mantener todo bundled
          return false;
        }
      },
    },

    resolve: {
      alias: {
        '@': path.resolve(__dirname, 'frontend'),
        '@ui': path.resolve(__dirname, 'frontend/components/ui'),
        '@layouts': path.resolve(__dirname, 'frontend/layouts'),
        '@pages': path.resolve(__dirname, 'frontend/pages'),
        '@assets': path.resolve(__dirname, 'frontend/assets'),
        '@composables': path.resolve(__dirname, 'frontend/composables'),
        '~': path.resolve(__dirname, 'resources'),
        vue: 'vue/dist/vue.esm-bundler.js',
      },
    },

    server: {
      host: '127.0.0.1',
      port: 5173,
      strictPort: false,
      hmr: {
        overlay: true
      },
      watch: {
        ignored: [
          '**/node_modules/**',
          '**/vendor/**',
          '**/.git/**',
          '**/storage/**'
        ]
      }
    },

    // Optimizaciones de dependencias
    optimizeDeps: {
      include: [
        'vue',
        '@inertiajs/vue3',
        '@vueuse/core',
        '@vueuse/components',
        '@headlessui/vue',
        '@heroicons/vue/24/outline',
        '@heroicons/vue/24/solid',
        'pinia',
        'dayjs',
        'axios'
      ],
      exclude: [
        'laravel-echo',
        'pusher-js'
      ]
    },

    css: {
      postcss: {
        plugins: [
          require('tailwindcss'),
          require('autoprefixer'),
        ],
      },
    },

    define: {
      __VUE_OPTIONS_API__: false,
      __VUE_PROD_DEVTOOLS__: !isProduction,
    },

    esbuild: {
      drop: isProduction ? ['console', 'debugger'] : [],
    },
  };
});