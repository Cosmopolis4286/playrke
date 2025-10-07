/**
 * Configuración del layer shared - Recursos compartidos entre admin y player.
 *
 * Este layer contiene componentes, composables, utilidades y stores
 * que son reutilizados por ambas secciones de la aplicación.
 */

export default defineNuxtConfig({
  // Configuración específica del layer shared
  alias: {
    '@shared': '~/layers/shared'
  },

  // CSS compartido
  css: [
    './assets/css/shared.css'
  ],

  // Componentes compartidos
  components: [
    {
      path: './components',
      pathPrefix: false,
      global: true
    }
  ],

  // Auto-imports para composables compartidos
  imports: {
    dirs: [
      './composables/**',
      './utils/**',
      './types/**'
    ]
  },

  // Plugins compartidos
  plugins: [
    './plugins/api.client.ts',
    './plugins/toast.client.ts',
    './plugins/auth.client.ts'
  ],

  // Módulos específicos para funcionalidad compartida
  modules: [
    '@pinia/nuxt',
    '@vueuse/nuxt'
  ],

  // Configuración de Pinia para stores compartidos
  pinia: {
    storesDirs: ['~/stores/**']
  },

  // Runtime config para el layer compartido
  runtimeConfig: {
    public: {
      shared: {
        version: '1.0.0',
        features: {
          notifications: true,
          darkMode: true,
          i18n: true
        }
      }
    }
  }
})