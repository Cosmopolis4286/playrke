import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'resources'),
      '@js': path.resolve(__dirname, 'resources/js'),
    }
  },
  test: {
    environment: 'happy-dom',
    globals: true,
    // Node >=22 expone un `localStorage` nativo experimental que gana
    // precedencia sobre el que happy-dom inyecta como global del entorno de
    // test, dejando localStorage.setItem/clear indefinidos. Se desactiva
    // para que el localStorage del entorno DOM sea el único disponible.
    // El flag solo existe desde Node 22: en Node 20 (CircleCI usa
    // cimg/node:20.19) los workers mueren con "not allowed in NODE_OPTIONS".
    env: Number(process.versions.node.split('.')[0]) >= 22
      ? { NODE_OPTIONS: '--no-experimental-webstorage' }
      : {}
  }
})
