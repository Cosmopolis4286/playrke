// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  // Compatibility date para Nitro
  compatibilityDate: '2025-10-26',

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
    public: {
      // apuntar al puerto mapeado en host (docker 8001:8000)
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8001',
      // WebSocket / Reverb
      pusherKey: process.env.NUXT_PUBLIC_PUSHER_KEY || 'local',
      pusherHost: process.env.NUXT_PUBLIC_PUSHER_HOST || 'localhost',
      pusherPort: Number(process.env.NUXT_PUBLIC_PUSHER_PORT || 8082),
      pusherTLS: process.env.NUXT_PUBLIC_PUSHER_TLS === 'true' ? true : false,
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

  // Configuración de componentes (orden de prioridad)
  components: [
    // 1. Componentes UI base (máxima prioridad)
    {
      path: '~/components/ui/base',
      prefix: 'UiBase',
      priority: 10
    },
    // 2. Componentes compartidos
    {
      path: '~/components/shared',
      prefix: 'Shared',
      priority: 5
    },
    // 3. Componentes auth
    {
      path: '~/components/auth',
      prefix: 'Auth',
      priority: 5
    },
    // 4. Resto de componentes sin prefijo
    {
      path: '~/components',
      pathPrefix: false,
      priority: 0
    }
  ],

  // Configuración SSR
  ssr: true,

  // Configuración experimental
  experimental: {
    payloadExtraction: false,
    // Desactivar preload de dev.json para evitar warning en desarrollo
    buildCache: false
  }
})