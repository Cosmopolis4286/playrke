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
  }
})
