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
    // Node >=22 exposes a native experimental localStorage that takes
    // precedence over happy-dom's, leaving setItem/clear undefined.
    // The flag to disable it only exists in Node >=22, so guard it.
    env: parseInt(process.versions.node.split('.')[0], 10) >= 22
      ? { NODE_OPTIONS: '--no-experimental-webstorage' }
      : {}
  }
})
