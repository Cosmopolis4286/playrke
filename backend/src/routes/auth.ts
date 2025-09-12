import type { FastifyInstance } from 'fastify';
import { AuthController } from '@/controllers/auth.js';
import { authMiddleware, userRateLimitMiddleware } from '@/middleware/auth.js';

/**
 * Rutas de autenticación
 * Migradas desde Laravel routes/auth.php
 */
export async function authRoutes(fastify: FastifyInstance) {
  const authController = new AuthController();

  // Prefix para todas las rutas de auth
  await fastify.register(async function (fastify) {
    
    // Rate limiting específico para auth
    const authRateLimit = userRateLimitMiddleware(10, 60000); // 10 requests por minuto

    // === RUTAS PÚBLICAS ===
    
    // Iniciar sesión
    fastify.post('/login', {
      preHandler: [authRateLimit],
      schema: {
        tags: ['auth'],
        summary: 'Iniciar sesión',
        body: {
          type: 'object',
          properties: {
            login: { 
              type: 'string',
              description: 'Email o nombre de usuario'
            },
            password: { 
              type: 'string',
              minLength: 1
            },
            remember_me: { 
              type: 'boolean',
              default: false
            }
          },
          required: ['login', 'password']
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
                  user: { type: 'object' },
                  token: { type: 'string' },
                  refresh_token: { type: 'string' },
                  expires_in: { type: 'number' },
                  token_type: { type: 'string' }
                }
              }
            }
          },
          401: {
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
    }, authController.login.bind(authController));

    // Registrar usuario
    fastify.post('/register', {
      preHandler: [authRateLimit],
      schema: {
        tags: ['auth'],
        summary: 'Registrar nuevo usuario',
        body: {
          type: 'object',
          properties: {
            username: { 
              type: 'string',
              minLength: 3,
              maxLength: 30
            },
            email: { 
              type: 'string',
              format: 'email'
            },
            password: { 
              type: 'string',
              minLength: 8
            },
            first_name: { 
              type: 'string',
              maxLength: 100
            },
            last_name: { 
              type: 'string',
              maxLength: 100
            },
            country_id: { 
              type: 'string',
              description: 'ID del país'
            },
            phone_number: { 
              type: 'string',
              pattern: '^\\+?[\\d\\s-()]{10,}$'
            },
            referral_code: { 
              type: 'string',
              description: 'Código de referido opcional'
            }
          },
          required: ['username', 'email', 'password', 'country_id']
        },
        response: {
          201: {
            type: 'object',
            properties: {
              success: { type: 'boolean' },
              message: { type: 'string' },
              data: {
                type: 'object',
                properties: {
                  user: { type: 'object' },
                  token: { type: 'string' },
                  refresh_token: { type: 'string' },
                  expires_in: { type: 'number' },
                  token_type: { type: 'string' }
                }
              }
            }
          },
          400: {
            type: 'object',
            properties: {
              success: { type: 'boolean' },
              message: { type: 'string' },
              errors: { type: 'array' }
            }
          }
        }
      }
    }, authController.register.bind(authController));

    // Renovar token
    fastify.post('/refresh', {
      schema: {
        tags: ['auth'],
        summary: 'Renovar token de acceso',
        body: {
          type: 'object',
          properties: {
            refresh_token: { 
              type: 'string',
              description: 'Token de actualización'
            }
          },
          required: ['refresh_token']
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
                  user: { type: 'object' },
                  token: { type: 'string' },
                  refresh_token: { type: 'string' },
                  expires_in: { type: 'number' },
                  token_type: { type: 'string' }
                }
              }
            }
          },
          401: {
            type: 'object',
            properties: {
              success: { type: 'boolean' },
              message: { type: 'string' },
              errors: { type: 'array' }
            }
          }
        }
      }
    }, authController.refresh.bind(authController));

    // === RUTAS PROTEGIDAS ===

    // Cerrar sesión
    fastify.post('/logout', {
      preHandler: [authMiddleware],
      schema: {
        tags: ['auth'],
        summary: 'Cerrar sesión actual',
        security: [{ bearerAuth: [] }],
        response: {
          200: {
            type: 'object',
            properties: {
              success: { type: 'boolean' },
              message: { type: 'string' },
              data: { type: 'null' }
            }
          }
        }
      }
    }, authController.logout.bind(authController));

    // Cerrar todas las sesiones
    fastify.post('/logout-all', {
      preHandler: [authMiddleware],
      schema: {
        tags: ['auth'],
        summary: 'Cerrar todas las sesiones',
        security: [{ bearerAuth: [] }],
        response: {
          200: {
            type: 'object',
            properties: {
              success: { type: 'boolean' },
              message: { type: 'string' },
              data: { type: 'null' }
            }
          }
        }
      }
    }, authController.logoutAll.bind(authController));

    // Obtener perfil del usuario
    fastify.get('/me', {
      preHandler: [authMiddleware],
      schema: {
        tags: ['auth'],
        summary: 'Obtener perfil del usuario autenticado',
        security: [{ bearerAuth: [] }],
        response: {
          200: {
            type: 'object',
            properties: {
              success: { type: 'boolean' },
              data: {
                type: 'object',
                properties: {
                  user: { type: 'object' },
                  roles: { 
                    type: 'array',
                    items: { type: 'string' }
                  },
                  permissions: { 
                    type: 'array',
                    items: { type: 'string' }
                  }
                }
              }
            }
          },
          401: {
            type: 'object',
            properties: {
              success: { type: 'boolean' },
              message: { type: 'string' },
              errors: { type: 'array' }
            }
          }
        }
      }
    }, authController.me.bind(authController));

    // Cambiar contraseña
    fastify.put('/password', {
      preHandler: [authMiddleware],
      schema: {
        tags: ['auth'],
        summary: 'Cambiar contraseña',
        security: [{ bearerAuth: [] }],
        body: {
          type: 'object',
          properties: {
            current_password: { 
              type: 'string',
              minLength: 1
            },
            new_password: { 
              type: 'string',
              minLength: 8
            },
            confirm_password: { 
              type: 'string',
              minLength: 8
            }
          },
          required: ['current_password', 'new_password', 'confirm_password']
        },
        response: {
          200: {
            type: 'object',
            properties: {
              success: { type: 'boolean' },
              message: { type: 'string' },
              data: { type: 'null' }
            }
          },
          400: {
            type: 'object',
            properties: {
              success: { type: 'boolean' },
              message: { type: 'string' },
              errors: { type: 'array' }
            }
          }
        }
      }
    }, authController.changePassword.bind(authController));

  }, { prefix: '/auth' });
}