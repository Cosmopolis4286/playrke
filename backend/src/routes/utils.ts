import type { FastifyInstance } from 'fastify';
import { UtilsController } from '@/controllers/utils.js';

/**
 * Rutas de utilidades
 * Migradas desde Laravel routes/modules/utils.php
 */
export async function utilsRoutes(fastify: FastifyInstance) {
  const utilsController = new UtilsController();

  // Prefix para todas las rutas de utils
  await fastify.register(async function (fastify) {
    
    // Manejo de cookies de referral
    fastify.get('/referral', {
      schema: {
        tags: ['utils'],
        summary: 'Obtener cookie de referral',
        response: {
          200: {
            type: 'object',
            properties: {
              success: { type: 'boolean' },
              data: {
                type: 'object',
                properties: {
                  referral: { type: ['string', 'null'] }
                }
              }
            }
          }
        }
      }
    }, utilsController.getReferral.bind(utilsController));

    fastify.post('/referral', {
      schema: {
        tags: ['utils'],
        summary: 'Establecer cookie de referral',
        body: {
          type: 'object',
          properties: {
            referral: { 
              type: 'string',
              pattern: '^[a-zA-Z0-9]{20}$',
              description: 'Código de referral de 20 caracteres alfanuméricos'
            }
          },
          required: ['referral']
        },
        response: {
          200: {
            type: 'object',
            properties: {
              success: { type: 'boolean' },
              message: { type: 'string' },
              data: {
                type: 'object',
                properties: {
                  referral: { type: 'string' }
                }
              }
            }
          },
          400: {
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
    }, utilsController.setReferral.bind(utilsController));

    // Información del servidor
    fastify.get('/server-info', {
      schema: {
        tags: ['utils'],
        summary: 'Obtener información del servidor',
        response: {
          200: {
            type: 'object',
            properties: {
              success: { type: 'boolean' },
              data: {
                type: 'object',
                properties: {
                  timestamp: { type: 'string' },
                  timezone: { type: 'string' },
                  uptime: { type: 'number' },
                  memory: { type: 'object' },
                  version: { 
                    type: 'object',
                    properties: {
                      node: { type: 'string' },
                      playrke: { type: 'string' }
                    }
                  },
                  environment: { type: 'string' }
                }
              }
            }
          }
        }
      }
    }, utilsController.getServerInfo.bind(utilsController));

    // Generar UUID
    fastify.get('/uuid', {
      schema: {
        tags: ['utils'],
        summary: 'Generar UUID v4',
        response: {
          200: {
            type: 'object',
            properties: {
              success: { type: 'boolean' },
              data: {
                type: 'object',
                properties: {
                  uuid: { type: 'string', format: 'uuid' }
                }
              }
            }
          }
        }
      }
    }, utilsController.generateUUID.bind(utilsController));

    // Configuración pública
    fastify.get('/config', {
      schema: {
        tags: ['utils'],
        summary: 'Obtener configuración pública',
        response: {
          200: {
            type: 'object',
            properties: {
              success: { type: 'boolean' },
              data: {
                type: 'object',
                properties: {
                  app: {
                    type: 'object',
                    properties: {
                      name: { type: 'string' },
                      version: { type: 'string' },
                      environment: { type: 'string' }
                    }
                  },
                  features: {
                    type: 'object',
                    properties: {
                      registration: { type: 'boolean' },
                      twoFactor: { type: 'boolean' },
                      referrals: { type: 'boolean' }
                    }
                  },
                  limits: {
                    type: 'object',
                    properties: {
                      maxFileSize: { type: 'number' },
                      maxUsernameLength: { type: 'number' },
                      maxEmailLength: { type: 'number' }
                    }
                  },
                  formats: {
                    type: 'object',
                    properties: {
                      dateFormat: { type: 'string' },
                      timeFormat: { type: 'string' },
                      datetimeFormat: { type: 'string' }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }, utilsController.getPublicConfig.bind(utilsController));

  }, { prefix: '/utils' });
}