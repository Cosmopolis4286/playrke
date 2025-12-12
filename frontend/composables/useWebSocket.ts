/**
 * Composable para manejar conexiones WebSocket
 *
 * Este composable es un wrapper sobre WebSocketService que:
 * - Proporciona una API reactiva para componentes Vue
 * - Maneja lifecycle hooks automáticamente SI está en un componente
 * - Puede usarse tanto en componentes como en plugins
 *
 * Arquitectura:
 * - Usa Singleton Pattern a través de useWebSocketService
 * - Separation of Concerns: lógica de conexión en service, reactivity en composable
 * - Safe Lifecycle Hooks: solo registra hooks si hay instancia de componente
 * - Lazy Initialization: Service se inicializa dentro de contexto válido de Nuxt
 */

import type { Channel, PresenceChannel } from 'laravel-echo'
import { useWebSocketService } from '~/layers/shared/services/websocket'

export interface ConnectedUser {
  id: string
  name: string
  [key: string]: any
}

/**
 * Composable principal de WebSocket
 * Puede usarse en componentes (con lifecycle) o en plugins (sin lifecycle)
 *
 * IMPORTANTE: Se inicializa dentro de un contexto válido de Nuxt (setup, plugin, etc.)
 */
export const useWebSocket = () => {
  const { user } = useAuth()

  // Obtener servicio dentro del contexto del composable (Nuxt context válido)
  const websocketService = useWebSocketService()

  // Crear ref reactivo para el estado de conexión
  // El servicio notificará cambios a través del patrón Observer
  const isConnected = ref(websocketService.isConnected)

  // Suscribirse a cambios en el estado de conexión
  const unsubscribe = websocketService.onConnectionChange((connected) => {
    isConnected.value = connected
  })

  /**
   * Conectar a WebSocket
   */
  const connect = async (): Promise<void> => {
    await websocketService.connect()
  }

  /**
   * Desconectar de WebSocket
   */
  const disconnect = (): void => {
    websocketService.disconnect()
  }

  /**
   * Escuchar notificaciones personales del usuario autenticado
   */
  const listenToNotifications = (callback: (notification: any) => void): void => {
    if (!user.value) {
      console.error('[WebSocket] User not authenticated')
      return
    }

    websocketService.listenToPrivateChannel(
      `App.Models.User.${user.value.id}`,
      '.notification',
      callback
    )
  }

  // Registrar cleanup solo si estamos en un componente
  // getCurrentInstance() retorna la instancia del componente actual
  // Si es null, significa que estamos en un plugin o fuera de setup()
  const componentInstance = getCurrentInstance()
  if (componentInstance) {
    onUnmounted(() => {
      // Desuscribirse de cambios de conexión
      unsubscribe()
      console.log('[WebSocket] Component unmounted, keeping connection for other components')
    })
  }

  return {
    // Estado reactivo (computed)
    isConnected,

    // Métodos de conexión
    connect,
    disconnect,

    // Métodos de canales (delegados al servicio)
    listenToChannel: websocketService.listenToChannel.bind(websocketService),
    listenToPrivateChannel: websocketService.listenToPrivateChannel.bind(websocketService),
    listenToPresenceChannel: websocketService.listenToPresenceChannel.bind(websocketService),
    leaveChannel: websocketService.leaveChannel.bind(websocketService),

    // Utilidades
    getUsersInChannel: websocketService.getUsersInChannel.bind(websocketService),
    isUserInChannel: websocketService.isUserInChannel.bind(websocketService),
    listenToNotifications
  }
}

/**
 * Composable específico para el juego de bingo
 * Encapsula la lógica de WebSocket para una sala de bingo
 */
export const useBingoWebSocket = (roomId: string) => {
  const {
    connect,
    disconnect,
    listenToChannel,
    listenToPresenceChannel,
    getUsersInChannel,
    isConnected
  } = useWebSocket()

  const bingoStore = useBingoStore()
  const channelName = `bingo.room.${roomId}`

  /**
   * Conecta a la sala de bingo
   */
  const joinRoom = async () => {
    await connect()

    // Escuchar eventos del juego
    listenToChannel(channelName, 'NumberCalled', (data: { number: number }) => {
      console.log('🎲 Number called:', data.number)
      bingoStore.callNumber(data.number)
    })

    listenToChannel(channelName, 'GameStarted', (data: any) => {
      console.log('🎮 Game started:', data)
      bingoStore.startGame(data.room)
    })

    listenToChannel(channelName, 'GameEnded', (data: any) => {
      console.log('🏁 Game ended:', data)
      bingoStore.endGame()
    })

    listenToChannel(channelName, 'WinnerDeclared', (data: { winner: any }) => {
      console.log('🏆 Winner declared:', data.winner)
      bingoStore.addWinner(data.winner)
    })

    // Canal de presencia para ver jugadores conectados
    listenToPresenceChannel(channelName, {
      onJoin: (user) => {
        console.log('👤 Player joined:', user)
      },
      onLeave: (user) => {
        console.log('👋 Player left:', user)
      }
    })
  }

  /**
   * Desconecta de la sala de bingo
   */
  const leaveRoom = () => {
    disconnect()
    bingoStore.clearGame()
  }

  /**
   * Obtiene jugadores conectados en la sala
   */
  const getPlayersInRoom = () => {
    return getUsersInChannel(channelName)
  }

  return {
    isConnected,
    joinRoom,
    leaveRoom,
    getPlayersInRoom
  }
}

/**
 * Composable para notificaciones en tiempo real
 */
export const useNotificationWebSocket = () => {
  const {
    connect,
    disconnect,
    listenToNotifications,
    isConnected
  } = useWebSocket()

  const uiStore = useUIStore()

  /**
   * Inicia escucha de notificaciones
   */
  const startListening = async () => {
    await connect()

    listenToNotifications((notification: any) => {
      console.log('🔔 Notification received:', notification)

      // Mostrar notificación en UI
      uiStore.notify({
        title: notification.title || 'Nueva Notificación',
        message: notification.message,
        type: notification.type || 'info'
      })
    })
  }

  /**
   * Detiene escucha de notificaciones
   */
  const stopListening = () => {
    disconnect()
  }

  return {
    isConnected,
    startListening,
    stopListening
  }
}

// Tipos para TypeScript
declare global {
  interface Window {
    Pusher: any
    Echo: any
  }
}

export default useWebSocket
