/**
 * Configuración del layer player - Interfaz de jugador.
 *
 * Este layer contiene toda la funcionalidad específica para
 * jugadores: lobby de juegos, cartones de bingo, perfil,
 * tienda y sistema de achievements.
 */

export default defineNuxtConfig({
  // Configuración específica del layer player
  alias: {
    '@player': '~/layers/player'
  },

  // CSS específico para player
  css: [
    './assets/css/player.css'
  ],

  // Componentes específicos de player
  components: [
    {
      path: './components',
      pathPrefix: false,
      global: true
    }
  ],

  // Auto-imports para composables de player
  imports: {
    dirs: [
      './composables/**',
      './utils/**'
    ]
  },

  // Plugins específicos de player
  plugins: [
    './plugins/game-engine.client.ts',
    './plugins/sound.client.ts',
    './plugins/websocket.client.ts'
  ],

  // Middleware específico de player
  router: {
    middleware: ['player-auth']
  },

  // Runtime config específico para player
  runtimeConfig: {
    public: {
      player: {
        theme: 'gaming',
        features: {
          lobby: true,
          bingoGame: true,
          profile: true,
          shop: true,
          achievements: true,
          leaderboard: true,
          chat: true
        },
        gameConfig: {
          maxCartones: 6,
          autoPlay: true,
          soundEffects: true,
          animations: true
        }
      }
    }
  },

  // Meta tags específicos para player
  app: {
    head: {
      title: 'Jugar - PlayRKE',
      meta: [
        { name: 'description', content: 'Juega bingo online en PlayRKE - La mejor experiencia de bingo en línea' },
        { property: 'og:type', content: 'game' }
      ]
    }
  }
})