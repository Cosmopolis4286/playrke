import type { FastifyInstance } from 'fastify'
import cors from '@fastify/cors'
import helmet from '@fastify/helmet'
import rateLimit from '@fastify/rate-limit'
import jwt from '@fastify/jwt'
import cookie from '@fastify/cookie'
import multipart from '@fastify/multipart'
import staticFiles from '@fastify/static'
import { config } from '@/config/env.js'
import path from 'path'

export const setupPlugins = async (fastify: FastifyInstance): Promise<void> => {
  // Security headers
  await fastify.register(helmet, {
    contentSecurityPolicy: {
      directives: {
        defaultSrc: ["'self'"],
        styleSrc: ["'self'", "'unsafe-inline'"],
        scriptSrc: ["'self'"],
        imgSrc: ["'self'", "data:", "https:"],
      },
    },
  })

  // CORS configuration
  await fastify.register(cors, {
    origin: (origin, cb) => {
      const hostname = new URL(origin || config.CORS_ORIGIN).hostname
      
      // Allow requests from development servers and production domains
      const allowedOrigins = [
        'localhost',
        '127.0.0.1',
        'playrke.com',
        'www.playrke.com'
      ]
      
      if (!origin || allowedOrigins.some(allowed => hostname.includes(allowed))) {
        cb(null, true)
      } else {
        cb(new Error('Not allowed by CORS'), false)
      }
    },
    credentials: true,
    methods: ['GET', 'PUT', 'POST', 'DELETE', 'OPTIONS', 'PATCH'],
    allowedHeaders: [
      'Origin',
      'X-Requested-With', 
      'Content-Type',
      'Accept',
      'Authorization',
      'X-CSRF-Token',
      'X-Forwarded-For'
    ]
  })

  // Rate limiting
  await fastify.register(rateLimit, {
    max: config.RATE_LIMIT_MAX,
    timeWindow: `${config.RATE_LIMIT_WINDOW} minutes`,
    skipOnError: true,
    errorResponseBuilder: (req, context) => ({
      error: 'Too Many Requests',
      message: `Rate limit exceeded. Try again in ${Math.round(context.ttl / 1000)} seconds.`,
      statusCode: 429,
      retryAfter: Math.round(context.ttl / 1000)
    })
  })

  // JWT authentication
  await fastify.register(jwt, {
    secret: config.JWT_SECRET,
    sign: {
      expiresIn: config.JWT_EXPIRES_IN
    },
    cookie: {
      cookieName: 'token',
      signed: false
    }
  })

  // Cookie support
  await fastify.register(cookie, {
    secret: config.SESSION_SECRET,
    parseOptions: {
      secure: config.NODE_ENV === 'production',
      httpOnly: true,
      sameSite: 'lax'
    }
  })

  // File upload support
  await fastify.register(multipart, {
    limits: {
      fieldNameSize: 100,
      fieldSize: 100,
      fields: 10,
      fileSize: config.MAX_FILE_SIZE,
      files: 5,
      headerPairs: 2000
    }
  })

  // Static files serving
  await fastify.register(staticFiles, {
    root: path.join(process.cwd(), config.UPLOAD_PATH),
    prefix: '/uploads/',
    decorateReply: false
  })

  // Request/Response logging for development
  if (config.NODE_ENV === 'development') {
    fastify.addHook('onRequest', async (request, reply) => {
      request.log.info(`📥 ${request.method} ${request.url}`)
    })

    fastify.addHook('onResponse', async (request, reply) => {
      request.log.info(`📤 ${request.method} ${request.url} - ${reply.statusCode}`)
    })
  }

  // Error handler
  fastify.setErrorHandler(async (error, request, reply) => {
    const { statusCode = 500, message } = error
    
    request.log.error({
      error: error.message,
      stack: error.stack,
      url: request.url,
      method: request.method,
      ip: request.ip
    })

    // Don't leak error details in production
    const isDev = config.NODE_ENV === 'development'
    
    if (statusCode === 429) {
      return reply.status(429).send({
        error: 'Too Many Requests',
        message: 'Rate limit exceeded',
        statusCode: 429
      })
    }

    if (statusCode === 401) {
      return reply.status(401).send({
        error: 'Unauthorized',
        message: 'Authentication required',
        statusCode: 401
      })
    }

    if (statusCode === 403) {
      return reply.status(403).send({
        error: 'Forbidden',
        message: 'Insufficient permissions',
        statusCode: 403
      })
    }

    if (statusCode < 500) {
      return reply.status(statusCode).send({
        error: error.name || 'Bad Request',
        message: message,
        statusCode,
        ...(isDev && { stack: error.stack })
      })
    }

    // Server errors
    return reply.status(500).send({
      error: 'Internal Server Error',
      message: isDev ? message : 'Something went wrong',
      statusCode: 500,
      ...(isDev && { stack: error.stack })
    })
  })

  // Not found handler
  fastify.setNotFoundHandler(async (request, reply) => {
    return reply.status(404).send({
      error: 'Not Found',
      message: `Route ${request.method}:${request.url} not found`,
      statusCode: 404
    })
  })
}