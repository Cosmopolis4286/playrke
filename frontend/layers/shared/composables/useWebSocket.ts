/**
 * Composable para manejar conexiones WebSocket con Laravel Echo
 *
 * Proporciona funcionalidades para:
 * - Conectar/desconectar de canales
 * - Escuchar eventos en tiempo real
 * - Presence channels (usuarios conectados)
 * - Private channels (autenticados)
 * - Manejo de reconexiones
 */

import type { Channel, PresenceChannel } from 'laravel-echo'

interface WebSocketConfig {
  broadcaster: 'pusher' | 'reverb'
  key: string
  wsHost?: string
  wsPort?: number
  wssPort?: number
  forceTLS?: boolean
  enabledTransports?: string[]
  cluster?: string
}

interface ConnectedUser {
  id: string
  name: string
  [key: string]: any
}

export const useWebSocket = () => {
  const config = useRuntimeConfig()
  const { user } = useAuth()

  // Estado
  const echo = ref<any>(null)
  const isConnected = ref(false)
  const activeChannels = ref<Map<string, Channel>>(new Map())
  const connectedUsers = ref<Map<string, ConnectedUser[]>>(new Map())

  /**
   * Inicializa la conexión con Laravel Echo
   */
  const connect = async (): Promise<void> => {
    if (echo.value) {
      console.warn('Echo already connected')
      return
    }

    try {
      // Importar dinámicamente para SSR
      if (process.client) {
        const { default: Echo } = await import('laravel-echo')
        const Pusher = (await import('pusher-js')).default

        // Configuración por defecto
        const wsConfig: WebSocketConfig = {
          broadcaster: 'pusher',
          key: config.public.pusherKey || 'local',
          wsHost: config.public.pusherHost || 'localhost',
          wsPort: config.public.pusherPort || 6001,
          wssPort: config.public.pusherPort || 6001,
          forceTLS: config.public.pusherTLS || false,
          enabledTransports: ['ws', 'wss'],
          cluster: config.public.pusherCluster || 'mt1'
        }

        // Configurar Pusher globalmente
        window.Pusher = Pusher

        // Crear instancia de Echo
        echo.value = new Echo({
          ...wsConfig,
          auth: {
            headers: {
              Accept: 'application/json'
            }
          },
          authEndpoint: `${config.public.apiBase.replace('/api', '')}/broadcasting/auth`
        })

        isConnected.value = true
        console.log('✅ WebSocket connected')
      }
    } catch (error) {
      console.error('❌ WebSocket connection error:', error)
      isConnected.value = false
    }
  }

  /**
   * Desconecta de todos los canales y cierra Echo
   */
  const disconnect = (): void => {
    if (!echo.value) return

    // Desconectar de todos los canales
    activeChannels.value.forEach((channel, channelName) => {
      leaveChannel(channelName)
    })

    echo.value.disconnect()
    echo.value = null
    isConnected.value = false
    activeChannels.value.clear()
    connectedUsers.value.clear()

    console.log('🔌 WebSocket disconnected')
  }

  /**
   * Escucha un canal público
   */
  const listenToChannel = (
    channelName: string,
    eventName: string,
    callback: (data: any) => void
  ): void => {
    if (!echo.value) {
      console.error('Echo not connected. Call connect() first.')
      return
    }

    let channel = activeChannels.value.get(channelName)

    if (!channel) {
      channel = echo.value.channel(channelName)
      activeChannels.value.set(channelName, channel)
    }

    channel.listen(eventName, callback)
    console.log(`👂 Listening to ${channelName} for ${eventName}`)
  }

  /**
   * Escucha un canal privado (requiere autenticación)
   */
  const listenToPrivateChannel = (
    channelName: string,
    eventName: string,
    callback: (data: any) => void
  ): void => {
    if (!echo.value) {
      console.error('Echo not connected. Call connect() first.')
      return
    }

    if (!user.value) {
      console.error('User not authenticated')
      return
    }

    let channel = activeChannels.value.get(`private-${channelName}`)

    if (!channel) {
      channel = echo.value.private(channelName)
      activeChannels.value.set(`private-${channelName}`, channel)
    }

    channel.listen(eventName, callback)
    console.log(`🔒 Listening to private-${channelName} for ${eventName}`)
  }

  /**
   * Escucha un canal de presencia (quién está conectado)
   */
  const listenToPresenceChannel = (
    channelName: string,
    callbacks: {
      onJoin?: (user: ConnectedUser) => void
      onLeave?: (user: ConnectedUser) => void
      onEvent?: (eventName: string, callback: (data: any) => void) => void
    }
  ): void => {
    if (!echo.value) {
      console.error('Echo not connected. Call connect() first.')
      return
    }

    if (!user.value) {
      console.error('User not authenticated')
      return
    }

    let channel = activeChannels.value.get(`presence-${channelName}`) as PresenceChannel

    if (!channel) {
      channel = echo.value.join(channelName) as PresenceChannel
      activeChannels.value.set(`presence-${channelName}`, channel)

      // Here event - cuando te unes al canal
      channel.here((users: ConnectedUser[]) => {
        connectedUsers.value.set(channelName, users)
        console.log(`👥 Users in ${channelName}:`, users.length)
      })

      // Joining event - cuando alguien se une
      channel.joining((joiningUser: ConnectedUser) => {
        const users = connectedUsers.value.get(channelName) || []
        users.push(joiningUser)
        connectedUsers.value.set(channelName, users)

        if (callbacks.onJoin) {
          callbacks.onJoin(joiningUser)
        }

        console.log(`➕ User joined ${channelName}:`, joiningUser)
      })

      // Leaving event - cuando alguien se va
      channel.leaving((leavingUser: ConnectedUser) => {
        const users = connectedUsers.value.get(channelName) || []
        const filtered = users.filter(u => u.id !== leavingUser.id)
        connectedUsers.value.set(channelName, filtered)

        if (callbacks.onLeave) {
          callbacks.onLeave(leavingUser)
        }

        console.log(`➖ User left ${channelName}:`, leavingUser)
      })
    }

    console.log(`👁️ Joined presence channel: ${channelName}`)
  }

  /**
   * Abandona un canal específico
   */
  const leaveChannel = (channelName: string): void => {
    if (!echo.value) return

    const channel = activeChannels.value.get(channelName)

    if (channel) {
      echo.value.leave(channelName.replace(/^(private-|presence-)/, ''))
      activeChannels.value.delete(channelName)
      connectedUsers.value.delete(channelName.replace('presence-', ''))
      console.log(`👋 Left channel: ${channelName}`)
    }
  }

  /**
   * Obtiene usuarios conectados en un canal de presencia
   */
  const getUsersInChannel = (channelName: string): ConnectedUser[] => {
    return connectedUsers.value.get(channelName) || []
  }

  /**
   * Verifica si un usuario está en un canal
   */
  const isUserInChannel = (channelName: string, userId: string): boolean => {
    const users = connectedUsers.value.get(channelName) || []
    return users.some(u => u.id === userId)
  }

  /**
   * Escucha evento de notificación personal
   */
  const listenToNotifications = (callback: (notification: any) => void): void => {
    if (!user.value) {
      console.error('User not authenticated')
      return
    }

    listenToPrivateChannel(
      `App.Models.User.${user.value.id}`,
      '.notification',
      callback
    )
  }

  // Limpiar al desmontar
  onUnmounted(() => {
    disconnect()
  })

  return {
    // Estado
    isConnected: readonly(isConnected),
    activeChannels: readonly(activeChannels),
    connectedUsers: readonly(connectedUsers),

    // Métodos de conexión
    connect,
    disconnect,

    // Métodos de canales
    listenToChannel,
    listenToPrivateChannel,
    listenToPresenceChannel,
    leaveChannel,

    // Utilidades
    getUsersInChannel,
    isUserInChannel,
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
