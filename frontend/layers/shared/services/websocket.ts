/**
 * WebSocket Service - Singleton Pattern
 *
 * Servicio centralizado para gestionar conexiones WebSocket.
 * No depende de lifecycle hooks de Vue, puede usarse en plugins.
 *
 * Principios aplicados:
 * - Singleton: Una única instancia de conexión
 * - Separation of Concerns: Lógica de conexión separada de Vue
 * - Lazy Initialization: Se conecta solo cuando es necesario
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

class WebSocketService {
  private static instance: WebSocketService
  private echo: any = null
  private _isConnected = ref(false)
  private activeChannels = new Map<string, Channel>()
  private connectedUsers = new Map<string, ConnectedUser[]>()
  private config: any = null

  private constructor() {
    // Constructor privado para Singleton
    if (process.client) {
      this.config = useRuntimeConfig()
    }
  }

  /**
   * Obtener instancia única del servicio
   */
  static getInstance(): WebSocketService {
    if (!WebSocketService.instance) {
      WebSocketService.instance = new WebSocketService()
    }
    return WebSocketService.instance
  }

  /**
   * Estado de conexión (readonly)
   */
  get isConnected() {
    return readonly(this._isConnected)
  }

  /**
   * Conectar a WebSocket
   */
  async connect(): Promise<void> {
    if (this.echo) {
      console.warn('[WebSocket] Already connected')
      return
    }

    if (!process.client) {
      console.warn('[WebSocket] Cannot connect on server side')
      return
    }

    try {
      const { default: Echo } = await import('laravel-echo')
      const Pusher = (await import('pusher-js')).default

      const wsConfig: WebSocketConfig = {
        broadcaster: 'pusher',
        key: this.config.public.pusherKey || 'local',
        wsHost: this.config.public.pusherHost || 'localhost',
        wsPort: this.config.public.pusherPort || 6001,
        wssPort: this.config.public.pusherPort || 6001,
        forceTLS: this.config.public.pusherTLS || false,
        enabledTransports: ['ws', 'wss'],
        cluster: this.config.public.pusherCluster || 'mt1'
      }

      window.Pusher = Pusher

      this.echo = new Echo({
        ...wsConfig,
        auth: {
          headers: {
            Accept: 'application/json'
          }
        },
        authEndpoint: `${this.config.public.apiBase.replace('/api', '')}/broadcasting/auth`
      })

      this._isConnected.value = true
      console.log('✅ [WebSocket] Connected successfully')
    } catch (error) {
      console.error('❌ [WebSocket] Connection error:', error)
      this._isConnected.value = false
    }
  }

  /**
   * Desconectar de WebSocket
   */
  disconnect(): void {
    if (!this.echo) return

    // Desconectar de todos los canales
    this.activeChannels.forEach((channel, channelName) => {
      this.leaveChannel(channelName)
    })

    this.echo.disconnect()
    this.echo = null
    this._isConnected.value = false
    this.activeChannels.clear()
    this.connectedUsers.clear()

    console.log('🔌 [WebSocket] Disconnected')
  }

  /**
   * Suscribirse a un canal público
   */
  listenToChannel(
    channelName: string,
    eventName: string,
    callback: (data: any) => void
  ): void {
    if (!this.echo) {
      console.error('[WebSocket] Not connected. Call connect() first.')
      return
    }

    let channel = this.activeChannels.get(channelName)

    if (!channel) {
      channel = this.echo.channel(channelName)
      this.activeChannels.set(channelName, channel)
    }

    channel.listen(eventName, callback)
    console.log(`👂 [WebSocket] Listening to ${channelName} for ${eventName}`)
  }

  /**
   * Suscribirse a un canal privado
   */
  listenToPrivateChannel(
    channelName: string,
    eventName: string,
    callback: (data: any) => void
  ): void {
    if (!this.echo) {
      console.error('[WebSocket] Not connected. Call connect() first.')
      return
    }

    let channel = this.activeChannels.get(`private-${channelName}`)

    if (!channel) {
      channel = this.echo.private(channelName)
      this.activeChannels.set(`private-${channelName}`, channel)
    }

    channel.listen(eventName, callback)
    console.log(`🔒 [WebSocket] Listening to private-${channelName} for ${eventName}`)
  }

  /**
   * Suscribirse a un canal de presencia
   */
  listenToPresenceChannel(
    channelName: string,
    callbacks: {
      onJoin?: (user: ConnectedUser) => void
      onLeave?: (user: ConnectedUser) => void
      onEvent?: (eventName: string, callback: (data: any) => void) => void
    }
  ): void {
    if (!this.echo) {
      console.error('[WebSocket] Not connected. Call connect() first.')
      return
    }

    let channel = this.activeChannels.get(`presence-${channelName}`) as PresenceChannel

    if (!channel) {
      channel = this.echo.join(channelName) as PresenceChannel
      this.activeChannels.set(`presence-${channelName}`, channel)

      channel.here((users: ConnectedUser[]) => {
        this.connectedUsers.set(channelName, users)
        console.log(`👥 [WebSocket] Users in ${channelName}:`, users.length)
      })

      channel.joining((joiningUser: ConnectedUser) => {
        const users = this.connectedUsers.get(channelName) || []
        users.push(joiningUser)
        this.connectedUsers.set(channelName, users)

        if (callbacks.onJoin) {
          callbacks.onJoin(joiningUser)
        }

        console.log(`➕ [WebSocket] User joined ${channelName}:`, joiningUser)
      })

      channel.leaving((leavingUser: ConnectedUser) => {
        const users = this.connectedUsers.get(channelName) || []
        const filtered = users.filter(u => u.id !== leavingUser.id)
        this.connectedUsers.set(channelName, filtered)

        if (callbacks.onLeave) {
          callbacks.onLeave(leavingUser)
        }

        console.log(`➖ [WebSocket] User left ${channelName}:`, leavingUser)
      })
    }

    console.log(`👁️ [WebSocket] Joined presence channel: ${channelName}`)
  }

  /**
   * Abandonar un canal
   */
  leaveChannel(channelName: string): void {
    if (!this.echo) return

    const channel = this.activeChannels.get(channelName)

    if (channel) {
      this.echo.leave(channelName.replace(/^(private-|presence-)/, ''))
      this.activeChannels.delete(channelName)
      this.connectedUsers.delete(channelName.replace('presence-', ''))
      console.log(`👋 [WebSocket] Left channel: ${channelName}`)
    }
  }

  /**
   * Obtener usuarios conectados en un canal
   */
  getUsersInChannel(channelName: string): ConnectedUser[] {
    return this.connectedUsers.get(channelName) || []
  }

  /**
   * Verificar si un usuario está en un canal
   */
  isUserInChannel(channelName: string, userId: string): boolean {
    const users = this.connectedUsers.get(channelName) || []
    return users.some(u => u.id === userId)
  }
}

// Exportar instancia única
export const websocketService = WebSocketService.getInstance()
