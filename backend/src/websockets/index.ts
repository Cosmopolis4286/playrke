import { Server } from 'socket.io'
import { Server as HttpServer } from 'http'
import { BingoWebSocketHandler } from './BingoWebSocketHandler'
import { logger } from '../utils/logger'
import { config } from '../config/env'

export class WebSocketManager {
  private io: Server
  private bingoHandler: BingoWebSocketHandler

  constructor(httpServer: HttpServer) {
    // Configurar Socket.IO
    this.io = new Server(httpServer, {
      cors: {
        origin: config.CORS_ORIGIN,
        methods: ['GET', 'POST'],
        credentials: true
      },
      transports: ['websocket', 'polling'],
      allowEIO3: true
    })

    // Inicializar manejadores
    this.bingoHandler = new BingoWebSocketHandler(this.io)

    // Configurar eventos globales
    this.setupGlobalEvents()

    logger.info('WebSocket server configurado exitosamente')
  }

  private setupGlobalEvents() {
    this.io.on('connection', (socket) => {
      logger.info(`Nuevo cliente conectado: ${socket.id} desde ${socket.handshake.address}`)

      // Enviar mensaje de bienvenida
      socket.emit('welcome', {
        message: 'Conectado al servidor PlayRKE',
        socketId: socket.id,
        timestamp: new Date().toISOString()
      })

      // Manejar ping/pong para mantener conexión activa
      socket.on('ping', () => {
        socket.emit('pong', { timestamp: Date.now() })
      })

      // Log de desconexión
      socket.on('disconnect', (reason) => {
        logger.info(`Cliente desconectado: ${socket.id}, razón: ${reason}`)
      })

      // Manejar errores
      socket.on('error', (error) => {
        logger.error(`Error en socket ${socket.id}:`, error)
      })
    })

    // Middleware global para logging
    this.io.use((socket, next) => {
      const start = Date.now()
      
      socket.onAny((event, ...args) => {
        const duration = Date.now() - start
        logger.debug(`WebSocket Event: ${event} en ${socket.id} (${duration}ms)`, {
          socketId: socket.id,
          event,
          duration
        })
      })

      next()
    })

    // Middleware de rate limiting (opcional)
    this.io.use((socket, next) => {
      // Implementar rate limiting si es necesario
      next()
    })
  }

  /**
   * Obtener instancia del manejador de Bingo
   */
  public getBingoHandler(): BingoWebSocketHandler {
    return this.bingoHandler
  }

  /**
   * Obtener instancia de Socket.IO
   */
  public getIO(): Server {
    return this.io
  }

  /**
   * Enviar mensaje a todos los clientes conectados
   */
  public broadcast(event: string, data: any) {
    this.io.emit(event, data)
    logger.info(`Mensaje broadcast enviado: ${event}`, { data })
  }

  /**
   * Enviar mensaje a un usuario específico
   */
  public sendToUser(userId: string, event: string, data: any) {
    // Implementar lógica para enviar a un usuario específico
    // Requiere mantener un mapeo de userId -> socketId
    logger.info(`Mensaje enviado a usuario ${userId}: ${event}`, { data })
  }

  /**
   * Obtener estadísticas de conexiones
   */
  public getStats() {
    const sockets = this.io.sockets.sockets
    const namespaces = Array.from(this.io._nsps.keys())
    
    return {
      totalConnections: sockets.size,
      namespaces: namespaces,
      bingoConnections: this.bingoHandler.getConnectedUsersCount(),
      uptime: process.uptime()
    }
  }

  /**
   * Cerrar todas las conexiones
   */
  public close() {
    logger.info('Cerrando servidor WebSocket...')
    this.io.close()
  }
}

// Instancia singleton del WebSocket manager
let webSocketManager: WebSocketManager | null = null

/**
 * Inicializar WebSocket server
 */
export function initializeWebSocket(httpServer: HttpServer): WebSocketManager {
  if (!webSocketManager) {
    webSocketManager = new WebSocketManager(httpServer)
  }
  return webSocketManager
}

/**
 * Obtener instancia del WebSocket manager
 */
export function getWebSocketManager(): WebSocketManager {
  if (!webSocketManager) {
    throw new Error('WebSocket manager no ha sido inicializado')
  }
  return webSocketManager
}