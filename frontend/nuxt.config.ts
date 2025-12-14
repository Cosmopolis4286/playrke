// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-10-26',

  devtools: {
    enabled: true,
    timeline: {
      enabled: true
    }
  },

  extends: [
    './layers/shared',
    './layers/admin',
    './layers/player'
  ],

  modules: [
    '@nuxt/ui',
    '@nuxt/icon',
    '@nuxtjs/tailwindcss',
    '@pinia/nuxt',
    '@vueuse/nuxt'
  ],

  css: [
    '~/assets/css/main.css'
  ],

  typescript: {
    typeCheck: false,
    strict: true
  },

  devServer: {
    port: 3000,
    host: '0.0.0.0'
  },

  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8001',
      reverbAppId: process.env.REVERB_APP_ID || '',
      reverbAppKey: process.env.REVERB_APP_KEY || '',
      reverbAppSecret: process.env.REVERB_APP_SECRET || '',
      reverbHost: process.env.REVERB_HOST || 'localhost',
      reverbPort: Number(process.env.REVERB_PORT || 8082),
      reverbTLS: process.env.REVERB_TLS === 'true' || false,
      pusherKey: process.env.NUXT_PUBLIC_PUSHER_KEY || 'local',
      pusherHost: process.env.NUXT_PUBLIC_PUSHER_HOST || 'localhost',
      pusherPort: Number(process.env.NUXT_PUBLIC_PUSHER_PORT || 8082),
      pusherCluster: process.env.NUXT_PUBLIC_PUSHER_CLUSTER || 'mt1',
      pusherTLS: process.env.NUXT_PUBLIC_PUSHER_TLS === 'true' || false,
      appName: 'PlayRKE',
      appDescription: 'Plataforma de Bingo Online'
    }
  },

  vite: {
    define: {
      __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: 'false'
    },
    server: {
      fs: {
        strict: false
      }
    }
  },

  components: [
    {
      path: '~/components/ui/base',
      prefix: 'UiBase',
      priority: 10
    },
    {
      path: '~/components/shared',
      prefix: 'Shared',
      priority: 5
    },
    {
      path: '~/components/auth',
      prefix: 'Auth',
      priority: 5
    },
    {
      path: '~/components',
      pathPrefix: false,
      priority: 0
    }
  ],

  ssr: true,

  experimental: {
    payloadExtraction: false,
    buildCache: false
  }
})
