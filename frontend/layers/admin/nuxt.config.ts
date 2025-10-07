/**
 * Configuración del layer admin - Interfaz de administración.
 *
 * Este layer contiene toda la funcionalidad específica para
 * administradores: dashboard, gestión de usuarios, configuración
 * del sistema, reportes y analytics.
 */

export default defineNuxtConfig({
  // Configuración específica del layer admin
  alias: {
    '@admin': '~/layers/admin'
  },

  // CSS específico para admin
  css: [
    './assets/css/admin.css'
  ],

  // Componentes específicos de admin
  components: [
    {
      path: './components',
      pathPrefix: false,
      global: true
    }
  ],

  // Auto-imports para composables de admin
  imports: {
    dirs: [
      './composables/**',
      './utils/**'
    ]
  },

  // Plugins específicos de admin
  plugins: [
    './plugins/admin-auth.client.ts',
    './plugins/charts.client.ts',
    './plugins/datatables.client.ts'
  ],

  // Middleware específico de admin
  router: {
    middleware: ['admin-auth']
  },

  // Runtime config específico para admin
  runtimeConfig: {
    public: {
      admin: {
        theme: 'blue',
        features: {
          dashboard: true,
          userManagement: true,
          gameConfig: true,
          reports: true,
          analytics: true,
          systemConfig: true
        },
        permissions: {
          superAdmin: true,
          moderator: true,
          support: true
        }
      }
    }
  },

  // Meta tags específicos para admin
  app: {
    head: {
      title: 'Admin Panel - PlayRKE',
      meta: [
        { name: 'robots', content: 'noindex, nofollow' },
        { name: 'description', content: 'Panel de administración de PlayRKE' }
      ]
    }
  }
})