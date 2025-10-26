/**
 * WebSocket Service - Lazy Singleton Pattern con Nuxt Context
 *
 * Servicio centralizado para gestionar conexiones WebSocket.
 * Implementa Lazy Singleton que se inicializa solo cuando se usa
 * y dentro de un contexto válido de Nuxt.
 *
 * Principios aplicados:
 * - Lazy Singleton: Instancia creada solo cuando se necesita
 * - Nuxt Context Safe: Se inicializa dentro de contexto válido
 * - Separation of Concerns: Lógica de conexión separada de Vue
 * - Framework Agnostic Core: Lógica pura, config inyectado
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

/**
 * WebSocket Service - Singleton Lazy
 *
 * IMPORTANTE: No se inicializa hasta que se llama a getInstance()
 * dentro de un contexto válido de Nuxt (plugin, composable en setup, etc.)
 */
class WebSocketService {
  private static instance: WebSocketService | null = null
  private echo: any = null
  private _isConnected: boolean = false
  private activeChannels = new Map<string, Channel>()
  private connectedUsers = new Map<string, ConnectedUser[]>()
  private runtimeConfig: any = null
  private connectionChangeListeners: Set<(connected: boolean) => void> = new Set()

  // Constructor privado - no se ejecuta hasta getInstance()
  private constructor(config: any) {
    this.runtimeConfig = config
  }

  /**
   * Obtener instancia única del servicio (Lazy Initialization)
   *
   * DEBE llamarse dentro de un contexto válido de Nuxt:
   * - Plugin de Nuxt
   * - Composable dentro de setup()
   * - Middleware de Nuxt
   * - Nuxt hook
   *
   * NO llamar en:
   * - Scope global de módulo
   * - Fuera de funciones setup()
   * - Callbacks async fuera de contexto
   */
  static getInstance(): WebSocketService {
    if (!WebSocketService.instance) {
      // Obtener config AQUÍ (dentro del contexto donde se llama getInstance)
      // No en el constructor global
      if (!process.client) {
        throw new Error('[WebSocket] Service can only be initialized on client side')
      }

      try {
        const config = useRuntimeConfig()
        WebSocketService.instance = new WebSocketService(config)
      } catch (error) {
        throw new Error(
          '[WebSocket] getInstance() must be called within a valid Nuxt context ' +
          '(plugin, composable in setup, middleware, or Nuxt hook). ' +
          'Error: ' + (error as Error).message
        )
      }
    }

    return WebSocketService.instance
  }

  /**
   * Reset de la instancia (útil para testing)
   */
  static resetInstance(): void {
    if (WebSocketService.instance) {
      WebSocketService.instance.disconnect()
      WebSocketService.instance = null
    }
  }

  /**
   * Estado de conexión
   * @returns boolean indicando si está conectado
   */
  get isConnected(): boolean {
    return this._isConnected
  }

  /**
   * Cambiar estado de conexión y notificar a listeners
   */
  private setConnected(value: boolean): void {
    if (this._isConnected !== value) {
      this._isConnected = value
      this.connectionChangeListeners.forEach(listener => listener(value))
    }
  }

  /**
   * Suscribirse a cambios en el estado de conexión
   * @returns función para desuscribirse
   */
  onConnectionChange(listener: (connected: boolean) => void): () => void {
    this.connectionChangeListeners.add(listener)
    // Llamar inmediatamente con el estado actual
    listener(this._isConnected)
    // Retornar función de cleanup
    return () => {
      this.connectionChangeListeners.delete(listener)
    }
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
        key: this.runtimeConfig.public.pusherKey || 'local',
        wsHost: this.runtimeConfig.public.pusherHost || 'localhost',
        wsPort: this.runtimeConfig.public.pusherPort || 6001,
        wssPort: this.runtimeConfig.public.pusherPort || 6001,
        forceTLS: this.runtimeConfig.public.pusherTLS || false,
        enabledTransports: ['ws', 'wss'],
        cluster: this.runtimeConfig.public.pusherCluster || 'mt1'
      }

      window.Pusher = Pusher

      this.echo = new Echo({
        ...wsConfig,
        auth: {
          headers: {
            Accept: 'application/json'
          }
        },
        authEndpoint: `${this.runtimeConfig.public.apiBase.replace('/api', '')}/broadcasting/auth`
      })

      this.setConnected(true)
      console.log('✅ [WebSocket] Connected successfully')
    } catch (error) {
      console.error('❌ [WebSocket] Connection error:', error)
      this.setConnected(false)
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
    this.setConnected(false)
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

/**
 * Helper function para obtener la instancia del servicio de forma segura
 *
 * Usar esta función en lugar de acceder directamente a WebSocketService.getInstance()
 * para obtener mejor error handling
 */
export function useWebSocketService(): WebSocketService {
  try {
    return WebSocketService.getInstance()
  } catch (error) {
    throw new Error(
      '[WebSocket] Failed to get service instance. ' +
      'Make sure you are calling this within a valid Nuxt context. ' +
      'Original error: ' + (error as Error).message
    )
  }
}

// NO exportar instancia directamente, exportar la clase y helper
export { WebSocketService }
