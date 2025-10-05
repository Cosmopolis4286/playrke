// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  // Configuración de desarrollo
  devtools: { enabled: true },

  // Extensión de layers para arquitectura modular
  extends: [
    './layers/shared',    // Recursos compartidos (base)
    './layers/admin',     // Capa de administración
    './layers/player'     // Capa de jugador
  ],

  // Módulos básicos de Nuxt
  modules: [
    // UI y diseño
    '@nuxt/ui',
    '@nuxt/icon',
    '@nuxtjs/tailwindcss',

    // Estado y datos
    '@pinia/nuxt',
    '@vueuse/nuxt'
  ],

  // Configuración CSS
  css: [
    '~/assets/css/main.css'
  ],

  // Configuración TypeScript
  typescript: {
    typeCheck: false, // Deshabilitado temporalmente para el build
    strict: true
  },

  // Configuración Tailwind CSS
  tailwindcss: {
    configPath: '~/tailwind.config.ts',
    config: {
      darkMode: 'class',
      content: [
        './components/**/*.{js,vue,ts}',
        './layers/**/*.{js,vue,ts}',
        './layouts/**/*.vue',
        './pages/**/*.vue',
        './plugins/**/*.{js,ts}',
        './app.vue'
      ]
    }
  },

  // Configuración de iconos
  icon: {
    size: '24px',
    class: '',
    mode: 'svg'
  },

  // Configuración del servidor de desarrollo
  devServer: {
    port: 3000,
    host: '0.0.0.0'
  },

  // Variables de entorno públicas
  runtimeConfig: {
    // Públicas (cliente y servidor)
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8000/api',
      appName: 'PlayRKE',
      appDescription: 'Plataforma de Bingo Online'
    }
  },

  // Configuración de Vite
  vite: {
    define: {
      __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: 'false'
    }
  },

  // Configuración de componentes
  components: [
    {
      path: '~/components',
      pathPrefix: false
    },
    {
      path: '~/layers/shared/components',
      pathPrefix: false
    }
  ],

  // Configuración SSR
  ssr: true,

  // Configuración experimental
  experimental: {
    payloadExtraction: false
  }
})