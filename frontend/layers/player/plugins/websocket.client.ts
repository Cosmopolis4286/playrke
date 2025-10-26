/**
 * Plugin de WebSocket para el layer de player
 *
 * Este plugin gestiona la conexión WebSocket de forma global para el contexto de player.
 * Usa useWebSocketService() dentro del contexto del plugin (Lazy Singleton Pattern).
 *
 * Arquitectura:
 * - Lazy Singleton Pattern: Instancia se crea al llamar useWebSocketService() dentro de contexto válido
 * - Plugin-Safe: No usa lifecycle hooks (onUnmounted), es seguro en plugins
 * - Auto-connect: Se conecta automáticamente cuando el usuario está autenticado
 * - Nuxt Context Safe: Servicio se inicializa dentro de contexto válido del plugin
 */

import { useWebSocketService } from '~/layers/shared/services/websocket'

export default defineNuxtPlugin(() => {
  const { user } = useAuth()

  // Obtener servicio dentro del contexto del plugin (contexto válido de Nuxt)
  const websocketService = useWebSocketService()

  // Crear ref reactivo para estado de conexión usando Observer pattern
  const isConnectedRef = ref(websocketService.isConnected)
  const unsubscribe = websocketService.onConnectionChange((connected) => {
    isConnectedRef.value = connected
  })

  // Usar watch en lugar de lifecycle hooks para manejar conexión/desconexión
  // Los watchers funcionan perfectamente en plugins
  let stopWatcher: (() => void) | null = null

  if (process.client) {
    stopWatcher = watch(
      user,
      async (newUser, oldUser) => {
        // Usuario acaba de autenticarse
        if (newUser && !oldUser && !isConnectedRef.value) {
          console.log('[Plugin WebSocket] User authenticated, connecting...')
          await websocketService.connect()
        }

        // Usuario cerró sesión
        if (!newUser && oldUser && isConnectedRef.value) {
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
        isConnected: readonly(isConnectedRef),

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
        // Desuscribirse del observer
        unsubscribe()
        // Opcionalmente desconectar WebSocket al destruir la app
        if (isConnectedRef.value) {
          console.log('[Plugin WebSocket] App unmounting, disconnecting...')
          websocketService.disconnect()
        }
      }
    }
  }
})
