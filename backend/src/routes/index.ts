import type { FastifyInstance } from 'fastify';
import { publicRoutes } from './public.js';
import { utilsRoutes } from './utils.js';
import { authRoutes } from './auth.js';
import { bingoRoutes } from './games/bingo.js';
import { paymentsRoutes } from './payments/index.js';
import { usersRoutes } from './users/index.js';
import { adminRoutes } from './admin.js';

/**
 * Configuración de todas las rutas de la aplicación
 * Punto de entrada centralizado para el sistema de rutas
 */
export async function setupRoutes(fastify: FastifyInstance) {
  
  // Configurar documentación OpenAPI/Swagger
  await fastify.register(import('@fastify/swagger'), {
    swagger: {
      info: {
        title: 'PlayRke API',
        description: 'API Backend para PlayRke - Plataforma de Gaming',
        version: '2.0.0'
      },
      host: process.env.API_HOST || 'localhost:3000',
      schemes: ['http', 'https'],
      consumes: ['application/json'],
      produces: ['application/json'],
      tags: [
        { name: 'public', description: 'Endpoints públicos' },
        { name: 'auth', description: 'Autenticación y autorización' },
        { name: 'utils', description: 'Utilidades y helpers' },
        { name: 'users', description: 'Gestión de usuarios' },
        { name: 'Bingo - Rooms', description: 'Gestión de salas de Bingo' },
        { name: 'Bingo - Cards', description: 'Gestión de cartones de Bingo' },
        { name: 'Bingo - Rounds', description: 'Gestión de rondas de Bingo' },
        { name: 'Payments - Wallets', description: 'Gestión de billeteras' },
        { name: 'Payments - Payments', description: 'Procesamiento de pagos' },
        { name: 'Payments - Methods', description: 'Métodos y configuración de pagos' },
        { name: 'Payments - Webhooks', description: 'Webhooks de gateways de pago' },
        { name: 'Users - Profile', description: 'Gestión de perfil de usuario' },
        { name: 'Users - Notifications', description: 'Sistema de notificaciones' },
        { name: 'Users - Admin', description: 'Administración de usuarios' },
        { name: 'Users - Admin Notifications', description: 'Administración de notificaciones' },
        { name: 'games', description: 'Sistema de juegos' },
        { name: 'payments', description: 'Pagos y billeteras' }
      ]
    }
  });

  await fastify.register(import('@fastify/swagger-ui'), {
    routePrefix: '/docs',
    uiConfig: {
      docExpansion: 'list',
      deepLinking: false
    },
    staticCSP: true,
    transformStaticCSP: (header) => header,
    transformSpecification: (swaggerObject, request, reply) => {
      return swaggerObject;
    },
    transformSpecificationClone: true
  });

  // Middleware global para todas las rutas
  fastify.addHook('preHandler', async (request, reply) => {
    // Log de todas las requests
    console.log(`${request.method} ${request.url} - ${request.ip}`);
  });

  // Hook para manejar errores globalmente
  fastify.setErrorHandler(async (error, request, reply) => {
    console.error('Global error handler:', error);

    // Error de validación de schema
    if (error.validation) {
      return reply.status(400).send({
        success: false,
        message: 'Datos de entrada no válidos',
        errors: error.validation.map((err: any) => ({
          field: err.instancePath?.replace('/', '') || err.params?.missingProperty || 'unknown',
          message: err.message || 'Valor no válido',
          code: 'VALIDATION_ERROR'
        }))
      });
    }

    // Error de rate limiting
    if (error.statusCode === 429) {
      return reply.status(429).send({
        success: false,
        message: 'Demasiadas solicitudes',
        errors: [{ field: 'rate_limit', message: 'Límite de solicitudes excedido', code: 'RATE_LIMIT' }]
      });
    }

    // Error genérico del servidor
    const statusCode = error.statusCode || 500;
    return reply.status(statusCode).send({
      success: false,
      message: statusCode === 500 ? 'Error interno del servidor' : error.message,
      errors: [{ 
        field: 'general', 
        message: error.message || 'Error interno del servidor', 
        code: error.code || 'INTERNAL_ERROR' 
      }]
    });
  });

  // Registrar rutas públicas
  await fastify.register(publicRoutes);
  
  // Registrar rutas de utilidades
  await fastify.register(utilsRoutes);
  
  // Registrar rutas de autenticación
  await fastify.register(authRoutes);

  // Registrar rutas de juegos
  await fastify.register(bingoRoutes, { prefix: '/api/bingo' });

  // Registrar rutas de pagos
  await fastify.register(paymentsRoutes, { prefix: '/api/payments' });

  // Registrar rutas de usuarios
  await fastify.register(usersRoutes, { prefix: '/api/users' });

  // Ruta catch-all para endpoints no encontrados
  fastify.setNotFoundHandler((request, reply) => {
    reply.status(404).send({
      success: false,
      message: 'Endpoint no encontrado',
      errors: [{ 
        field: 'endpoint', 
        message: `${request.method} ${request.url} no existe`, 
        code: 'NOT_FOUND' 
      }]
    });
  });
}