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

  static resetInstance(): void {
    if (WebSocketService.instance) {
      WebSocketService.instance.disconnect()
      WebSocketService.instance = null
    }
  }

  /** Estado de conexión */
  get isConnected(): boolean {
    return this._isConnected
  }

  /** Cambiar estado de conexión y notificar a listeners */
  private setConnected(value: boolean): void {
    if (this._isConnected !== value) {
      this._isConnected = value
      this.connectionChangeListeners.forEach(listener => listener(value))
    }
  }

  /**
   * Suscribirse a cambios en el estado de conexión
   * @param listener Callback con el nuevo estado de conexión
   * @returns función para desuscribirse
   */
  onConnectionChange(listener: (connected: boolean) => void): () => void {
    this.connectionChangeListeners.add(listener)
    listener(this._isConnected)
    return () => {
      this.connectionChangeListeners.delete(listener)
    }
  }

  /** Conectar a WebSocket */
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
      const publicCfg = this.runtimeConfig?.public ?? {}

      const pusherKey = publicCfg.pusherKey ?? 'local'
      const pusherHost = publicCfg.pusherHost ?? 'localhost'
      const pusherPort = Number(publicCfg.pusherPort ?? 8082)
      const pusherTLS = publicCfg.pusherTLS === 'true' || publicCfg.pusherTLS === true
      const forceTLS = pusherTLS
      const wsProtocol = forceTLS ? 'wss' : 'ws'
      const wsHost = pusherHost.replace(/\/$/, '')

      window.Pusher = Pusher

      const apiBase = (publicCfg.apiBase ?? '').replace(/\/$/, '')
      const authEndpoint = `${apiBase}/api/broadcasting/auth`

      console.debug('[WebSocket] Config loaded:', {
        pusherKey,
        wsHost,
        pusherPort,
        forceTLS,
        wsProtocol,
        authEndpoint
      })

      const echoConfig: any = {
        broadcaster: 'pusher',
        key: pusherKey,
        wsHost,
        wsPort: pusherPort,
        wssPort: pusherPort,
        forceTLS,
        enabledTransports: ['ws', 'wss'],
        auth: {
          headers: { Accept: 'application/json' },
          // Ignorar TS error, es aceptado por laravel-echo/pusher internamente
          // @ts-ignore
          withCredentials: true
        },
        authEndpoint
      }

      // Solo agregar cluster si está definido y no es vacío (para Pusher Cloud, no Reverb)
      if (publicCfg.pusherCluster && publicCfg.pusherCluster.trim() !== '') {
        echoConfig.cluster = publicCfg.pusherCluster
      }

      this.echo = new Echo(echoConfig)

      this.setConnected(true)
      console.log('✅ [WebSocket] Connected successfully to', `${wsProtocol}://${wsHost}:${pusherPort}`)
    } catch (error) {
      console.error('❌ [WebSocket] Connection error:', error)
      this.setConnected(false)
    }
  }

  /** Desconectar de WebSocket */
  disconnect(): void {
    if (!this.echo) return

    this.activeChannels.forEach((_channel, channelName) => {
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
   * @param channelName Nombre del canal
   * @param eventName Nombre del evento
   * @param callback Callback con datos recibidos
   */
  listenToChannel(channelName: string, eventName: string, callback: (data: any) => void): void {
    if (!this.echo) {
      console.error('[WebSocket] Not connected. Call connect() first.')
      return
    }

    let channel = this.activeChannels.get(channelName)

    if (!channel) {
      channel = this.echo.channel(channelName)
      this.activeChannels.set(channelName, channel!)
    }

    if (channel) {
      channel.listen(eventName, callback)
    } else {
      console.error(`[WebSocket] Channel ${channelName} no está definido para escuchar ${eventName}`)
    }
  }

  /**
   * Suscribirse a un canal privado
   * @param channelName Nombre del canal privado
   * @param eventName Nombre del evento
   * @param callback Callback con datos recibidos
   */
  listenToPrivateChannel(channelName: string, eventName: string, callback: (data: any) => void): void {
    if (!this.echo) {
      console.error('[WebSocket] Not connected. Call connect() first.')
      return
    }

    let channel = this.activeChannels.get(`private-${channelName}`)

    if (!channel) {
      channel = this.echo.private(channelName)
      this.activeChannels.set(`private-${channelName}`, channel!)
    }

    if (channel) {
      channel.listen(eventName, callback)
    } else {
      console.error(`[WebSocket] Private-Channel ${channelName} no está definido para escuchar ${eventName}`)
    }
  }

  /**
   * Suscribirse a un canal de presencia
   * @param channelName Nombre del canal de presencia
   * @param callbacks Callbacks para eventos de usuario
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

    let channel = this.activeChannels.get(`presence-${channelName}`) as PresenceChannel | undefined

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

        callbacks.onJoin?.(joiningUser)

        console.log(`➕ [WebSocket] User joined ${channelName}:`, joiningUser)
      })

      channel.leaving((leavingUser: ConnectedUser) => {
        const users = this.connectedUsers.get(channelName) || []
        const filtered = users.filter(u => u.id !== leavingUser.id)
        this.connectedUsers.set(channelName, filtered)

        callbacks.onLeave?.(leavingUser)

        console.log(`➖ [WebSocket] User left ${channelName}:`, leavingUser)
      })
    }

    console.log(`👁️ [WebSocket] Joined presence channel: ${channelName}`)
  }

  /**
   * Abandonar un canal
   * @param channelName Nombre del canal a abandonar
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
   * @param channelName Nombre del canal
   * @returns Array de usuarios conectados
   */
  getUsersInChannel(channelName: string): ConnectedUser[] {
    return this.connectedUsers.get(channelName) ?? []
  }

  /**
   * Verificar si un usuario está en un canal
   * @param channelName Nombre del canal
   * @param userId ID del usuario
   * @returns true si el usuario está en el canal
   */
  isUserInChannel(channelName: string, userId: string): boolean {
    const users = this.connectedUsers.get(channelName) ?? []
    return users.some(u => u.id === userId)
  }
}

/**
 * Helper para obtener la instancia del WebSocketService de forma segura
 * @returns instancia única de WebSocketService
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

export { WebSocketService }
