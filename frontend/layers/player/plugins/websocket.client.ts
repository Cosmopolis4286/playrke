/**
 * Plugin de WebSocket para el layer de player
 *
 * Este plugin gestiona la conexión WebSocket de forma global para el contexto de player.
 * Usa websocketService directamente (patrón Singleton) para evitar problemas con lifecycle hooks.
 *
 * Arquitectura:
 * - Singleton Pattern: Una única instancia de conexión WebSocket
 * - Plugin-Safe: No usa lifecycle hooks (onUnmounted), es seguro en plugins
 * - Auto-connect: Se conecta automáticamente cuando el usuario está autenticado
 */

import { websocketService } from '~/layers/shared/services/websocket'

export default defineNuxtPlugin(() => {
  const { user } = useAuth()

  // Usar watch en lugar de lifecycle hooks para manejar conexión/desconexión
  // Los watchers funcionan perfectamente en plugins
  let stopWatcher: (() => void) | null = null

  if (process.client) {
    stopWatcher = watch(
      user,
      async (newUser, oldUser) => {
        // Usuario acaba de autenticarse
        if (newUser && !oldUser && !websocketService.isConnected.value) {
          console.log('[Plugin WebSocket] User authenticated, connecting...')
          await websocketService.connect()
        }

        // Usuario cerró sesión
        if (!newUser && oldUser && websocketService.isConnected.value) {
          console.log('[Plugin WebSocket] User logged out, disconnecting...')
          websocketService.disconnect()
        }
      },
      { immediate: true }
    )
  }

  // Proveer acceso al servicio WebSocket a través del plugin
  return {
    provide: {
      ws: {
        // Métodos del servicio disponibles globalmente
        connect: () => websocketService.connect(),
        disconnect: () => websocketService.disconnect(),
        isConnected: computed(() => websocketService.isConnected.value),

        // Acceso al servicio completo para casos avanzados
        service: websocketService
      }
    },

    // Hook de cleanup de Nuxt (no es lifecycle de Vue)
    // Este se ejecuta cuando se destruye la app, no el componente
    hooks: {
      'app:beforeUnmount': () => {
        if (stopWatcher) {
          stopWatcher()
        }
        // Opcionalmente desconectar WebSocket al destruir la app
        if (websocketService.isConnected.value) {
          console.log('[Plugin WebSocket] App unmounting, disconnecting...')
          websocketService.disconnect()
        }
      }
    }
  }
})
