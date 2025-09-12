import Fastify from 'fastify'
import { config } from '@/config/env.js'
import { setupPlugins } from '@/config/plugins.js'
import { setupRoutes } from '@/routes/index.js'
import { logger } from '@/utils/logger.js'
import { dbService } from '@/services/database.js'
import { initializeWebSocket } from '@/websockets/index.js'

const fastify = Fastify({
  logger: {
    level: config.LOG_LEVEL,
    ...(config.NODE_ENV === 'production' && {
      file: config.LOG_FILE
    })
  }
})

// Variables globales para WebSocket
let webSocketManager: any = null

// Graceful shutdown
const gracefulShutdown = (signal: string) => {
  logger.info(`Received ${signal}. Graceful shutdown...`)
  
  // Cerrar WebSocket primero
  if (webSocketManager) {
    webSocketManager.close()
  }
  
  fastify.close().then(async () => {
    await dbService.disconnect()
    logger.info('Server closed successfully')
    process.exit(0)
  }).catch(async (err) => {
    logger.error('Error during graceful shutdown', err)
    await dbService.disconnect()
    process.exit(1)
  })
}

process.on('SIGINT', () => gracefulShutdown('SIGINT'))
process.on('SIGTERM', () => gracefulShutdown('SIGTERM'))

// Unhandled errors
process.on('unhandledRejection', (reason, promise) => {
  logger.error('Unhandled Rejection at:', promise, 'reason:', reason)
  process.exit(1)
})

process.on('uncaughtException', (error) => {
  logger.error('Uncaught Exception thrown:', error)
  process.exit(1)
})

const start = async () => {
  try {
    // Connect to database first
    await dbService.connect()
    
    // Setup plugins (CORS, JWT, etc.)
    await setupPlugins(fastify)
    
    // Setup routes
    await setupRoutes(fastify)
    
    // Start server
    await fastify.listen({ 
      port: config.PORT, 
      host: config.HOST 
    })

    // Initialize WebSocket server
    const httpServer = fastify.server
    webSocketManager = initializeWebSocket(httpServer)
    
    logger.info(`🚀 PlayRKE Backend API running on http://${config.HOST}:${config.PORT}`)
    logger.info(`📚 Environment: ${config.NODE_ENV}`)
    logger.info(`📄 API Docs: http://${config.HOST}:${config.PORT}/docs`)
    logger.info(`🔐 JWT enabled: ${!!config.JWT_SECRET}`)
    logger.info(`📧 Email enabled: ${!!config.MAIL_HOST}`)
    logger.info(`🔌 WebSocket server initialized for real-time gaming`)
    logger.info(`🎮 Bingo WebSocket namespace: /bingo`)
    
  } catch (err) {
    logger.error('Error starting server:', err)
    await dbService.disconnect()
    process.exit(1)
  }
}

// Development hot reload
if (config.NODE_ENV === 'development') {
  const { register } = await import('module')
  register('tsx/esm', import.meta.url)
}

start()