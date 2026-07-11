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
    env: {
      NODE_OPTIONS: '--no-experimental-webstorage'
    }
  }
})
