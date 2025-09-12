import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
  plugins: [
    laravel({
      input: [
        'resources/css/shared/styles.css',
        'resources/js/app.ts'
      ],
      refresh: true,
    }),
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),
  ],

  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'resources/js'),
      '~': path.resolve(__dirname, 'resources'),
      vue: 'vue/dist/vue.esm-bundler.js',
    },
  },

  server: {
    host: '127.0.0.1',
    port: 5173,
    hmr: {
      overlay: true
    }
  },

  optimizeDeps: {
    include: [
      'vue',
      'axios'
    ]
  },

  define: {
    __VUE_OPTIONS_API__: false,
    __VUE_PROD_DEVTOOLS__: true,
  }
});