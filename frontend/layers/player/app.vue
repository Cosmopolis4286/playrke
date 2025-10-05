<template>
  <div class="player-app min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900">
    <!-- Header de jugador -->
    <PlayerHeader />

    <!-- Contenido principal -->
    <main class="relative">
      <!-- Background dinámico para gaming -->
      <PlayerBackground />

      <!-- Contenido de la página -->
      <div class="relative z-10">
        <NuxtPage />
      </div>
    </main>

    <!-- Footer de jugador -->
    <PlayerFooter />

    <!-- Componentes overlay -->
    <PlayerChat v-if="isChatEnabled" />
    <PlayerNotifications />
    <PlayerModals />

    <!-- Loader de juego -->
    <GameLoader v-if="isGameLoading" />
  </div>
</template>

<script setup lang="ts">
/**
 * Layout principal para la sección de jugador.
 *
 * Proporciona una experiencia gaming inmersiva con background
 * dinámico, header con información del usuario, chat en vivo
 * y sistema de notificaciones optimizado para juegos.
 */

// Meta configuración para player
useHead({
  titleTemplate: '%s | PlayRKE - Bingo Online',
  meta: [
    { name: 'description', content: 'Juega bingo online en PlayRKE - La mejor experiencia de bingo en línea' },
    { property: 'og:type', content: 'game' },
    { name: 'theme-color', content: '#1e40af' }
  ],
  bodyAttrs: {
    class: 'player-layout gaming-mode'
  }
})

// Store de autenticación de player
const { isAuthenticated, user } = usePlayerAuth()

// Store de configuración de juego
const gameConfig = useGameConfig()

// Estados reactivos
const isChatEnabled = computed(() => gameConfig.features.chat && user.value?.preferences?.chat !== false)
const isGameLoading = ref(false)

// Configuración de WebSocket para tiempo real
const { connect, disconnect } = useWebSocket()

// Conectar WebSocket al montar
onMounted(async () => {
  if (!isAuthenticated.value) {
    await navigateTo('/login')
    return
  }

  // Conectar WebSocket para actualizaciones en tiempo real
  await connect()
})

// Desconectar WebSocket al desmontar
onUnmounted(() => {
  disconnect()
})

// Configuración de SEO para gaming
useSeoMeta({
  ogType: 'game',
  twitterCard: 'summary_large_image'
})

// Configuración de PWA para experiencia móvil
useHead({
  meta: [
    { name: 'apple-mobile-web-app-capable', content: 'yes' },
    { name: 'apple-mobile-web-app-status-bar-style', content: 'black-translucent' },
    { name: 'mobile-web-app-capable', content: 'yes' }
  ]
})
</script>

<style scoped>
/**
 * Estilos específicos para el layout de jugador.
 * Diseño gaming con efectos visuales y animaciones.
 */

.player-app {
  /* Variables CSS específicas para gaming */
  --player-primary: #ef4444;
  --player-secondary: #3b82f6;
  --player-accent: #10b981;
  --player-gradient: linear-gradient(135deg, #1e3a8a 0%, #1e40af 50%, #1e3a8a 100%);
}

/* Background gaming con efectos */
.player-app::before {
  content: '';
  @apply absolute inset-0 opacity-10;
  background-image:
    radial-gradient(circle at 20% 50%, rgba(59, 130, 246, 0.3) 0%, transparent 50%),
    radial-gradient(circle at 80% 20%, rgba(239, 68, 68, 0.3) 0%, transparent 50%),
    radial-gradient(circle at 40% 80%, rgba(16, 185, 129, 0.3) 0%, transparent 50%);
  animation: float 20s ease-in-out infinite;
}

/* Animaciones gaming */
@keyframes float {
  0%, 100% { transform: translateY(0px) rotate(0deg); }
  33% { transform: translateY(-10px) rotate(1deg); }
  66% { transform: translateY(5px) rotate(-1deg); }
}

/* Efectos de hover y interacción */
.player-app main {
  @apply transition-all duration-500 ease-out;
}

/* Responsive gaming layout */
@media (max-width: 768px) {
  .player-app {
    /* Optimización para móviles gaming */
    --mobile-safe-area: env(safe-area-inset-bottom);
    padding-bottom: var(--mobile-safe-area);
  }
}

/* Modo gaming full-screen */
.gaming-mode {
  @apply select-none;
  -webkit-touch-callout: none;
  -webkit-user-select: none;
  -webkit-tap-highlight-color: transparent;
}

/* Tema personalizado para gaming */
.dark .player-app {
  --player-bg: #0f172a;
  --player-surface: #1e293b;
}
</style>