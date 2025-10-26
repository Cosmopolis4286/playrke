/**
 * Plugin de WebSocket para comunicación en tiempo real
 * Integra useWebSocket para el layer de player
 */

export default defineNuxtPlugin(() => {
  const { connect, disconnect, isConnected } = useWebSocket()

  // Conectar automáticamente si el usuario está autenticado
  const { user } = useAuth()

  watch(user, (newUser) => {
    if (newUser && !isConnected.value) {
      connect()
    } else if (!newUser && isConnected.value) {
      disconnect()
    }
  }, { immediate: true })

  // Nota: No usar onUnmounted en plugins, los plugins no son componentes
  // La desconexión se maneja automáticamente cuando el usuario cierra sesión

  return {
    provide: {
      ws: {
        connect,
        disconnect,
        isConnected: readonly(isConnected)
      }
    }
  }
})
