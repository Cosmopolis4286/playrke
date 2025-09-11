import Fastify from 'fastify'
import { config } from '@/config/env.js'
import { setupPlugins } from '@/config/plugins.js'
import { setupRoutes } from '@/routes/index.js'
import { logger } from '@/utils/logger.js'

const fastify = Fastify({
  logger: {
    level: config.LOG_LEVEL,
    ...(config.NODE_ENV === 'production' && {
      file: config.LOG_FILE
    })
  }
})

// Graceful shutdown
const gracefulShutdown = (signal: string) => {
  logger.info(`Received ${signal}. Graceful shutdown...`)
  
  fastify.close().then(() => {
    logger.info('Server closed successfully')
    process.exit(0)
  }).catch((err) => {
    logger.error('Error during graceful shutdown', err)
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
    // Setup plugins (CORS, JWT, etc.)
    await setupPlugins(fastify)
    
    // Setup routes
    await setupRoutes(fastify)
    
    // Health check
    fastify.get('/health', async () => ({
      status: 'ok',
      timestamp: new Date().toISOString(),
      uptime: process.uptime(),
      version: '1.0.0',
      environment: config.NODE_ENV
    }))
    
    // Start server
    await fastify.listen({ 
      port: config.PORT, 
      host: config.HOST 
    })
    
    logger.info(`🚀 PlayRKE Backend API running on http://${config.HOST}:${config.PORT}`)
    logger.info(`📚 Environment: ${config.NODE_ENV}`)
    logger.info(`🔐 JWT enabled: ${!!config.JWT_SECRET}`)
    logger.info(`📧 Email enabled: ${!!config.MAIL_HOST}`)
    
  } catch (err) {
    logger.error('Error starting server:', err)
    process.exit(1)
  }
}

// Development hot reload
if (config.NODE_ENV === 'development') {
  const { register } = await import('module')
  register('tsx/esm', import.meta.url)
}

start()