import type { FastifyInstance } from 'fastify';
import { PublicController } from '@/controllers/public.js';

/**
 * Rutas públicas (sin autenticación)
 * Migradas desde Laravel routes/modules/public.php
 */
export async function publicRoutes(fastify: FastifyInstance) {
  const publicController = new PublicController();

  // Ruta raíz - información de la aplicación
  fastify.get('/', {
    schema: {
      tags: ['public'],
      summary: 'Información de la aplicación',
      response: {
        200: {
          type: 'object',
          properties: {
            success: { type: 'boolean' },
            data: {
              type: 'object',
              properties: {
                register: { type: 'boolean' },
                passwordRequestUrl: { type: 'string' },
                appName: { type: 'string' },
                version: { type: 'string' }
              }
            }
          }
        }
      }
    }
  }, publicController.root.bind(publicController));

  // Páginas de información estática
  fastify.get('/info/:page', {
    schema: {
      tags: ['public'],
      summary: 'Páginas de información estática',
      params: {
        type: 'object',
        properties: {
          page: {
            type: 'string',
            enum: ['terms', 'about', 'privacy']
          }
        },
        required: ['page']
      },
      response: {
        200: {
          type: 'object',
          properties: {
            success: { type: 'boolean' },
            data: {
              type: 'object',
              properties: {
                page: { type: 'string' },
                title: { type: 'string' },
                content: { type: 'string' }
              }
            }
          }
        },
        404: {
          type: 'object',
          properties: {
            success: { type: 'boolean' },
            message: { type: 'string' },
            errors: {
              type: 'array',
              items: {
                type: 'object',
                properties: {
                  field: { type: 'string' },
                  message: { type: 'string' },
                  code: { type: 'string' }
                }
              }
            }
          }
        }
      }
    }
  }, publicController.info.bind(publicController));

  // Health check
  fastify.get('/health', {
    schema: {
      tags: ['public'],
      summary: 'Health check del servidor',
      response: {
        200: {
          type: 'object',
          properties: {
            success: { type: 'boolean' },
            data: {
              type: 'object',
              properties: {
                status: { type: 'string' },
                timestamp: { type: 'string' },
                uptime: { type: 'number' },
                version: { type: 'string' },
                environment: { type: 'string' },
                services: {
                  type: 'object',
                  properties: {
                    database: { type: 'string' }
                  }
                }
              }
            }
          }
        }
      }
    }
  }, publicController.health.bind(publicController));
}