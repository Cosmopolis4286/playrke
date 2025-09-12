import { FastifyInstance } from 'fastify'
import { UserController } from '../../controllers/users/UserController'
import { NotificationController } from '../../controllers/users/NotificationController'
import { authenticateUser, requireRole } from '../../middleware/auth'

export async function usersRoutes(fastify: FastifyInstance) {
  const userController = new UserController()
  const notificationController = new NotificationController()

  // Aplicar middleware de autenticación a todas las rutas
  fastify.addHook('onRequest', authenticateUser)

  // === RUTAS DE PERFIL DE USUARIO ===

  // Obtener mi perfil
  fastify.get('/me', {
    schema: {
      tags: ['Users - Profile'],
      summary: 'Obtener mi perfil de usuario',
      querystring: {
        type: 'object',
        properties: {
          includeProfile: { type: 'boolean', default: false, description: 'Incluir configuraciones y estadísticas' }
        }
      },
      response: {
        200: {
          description: 'Perfil obtenido exitosamente',
          type: 'object',
          properties: {
            success: { type: 'boolean' },
            data: { type: 'object' },
            message: { type: 'string' }
          }
        }
      }
    }
  }, userController.getMyProfile.bind(userController))

  // Actualizar mi perfil
  fastify.put('/me/profile', {
    schema: {
      tags: ['Users - Profile'],
      summary: 'Actualizar mi perfil de usuario',
      body: {
        type: 'object',
        properties: {
          firstName: { type: 'string', minLength: 2, maxLength: 50 },
          lastName: { type: 'string', minLength: 2, maxLength: 50 },
          phoneNumber: { type: 'string', pattern: '^\\+?[1-9]\\d{1,14}$' },
          countryId: { type: 'string', format: 'uuid' },
          timeZoneId: { type: 'string', format: 'uuid' },
          profilePhotoUrl: { type: 'string', format: 'uri' }
        }
      }
    }
  }, userController.updateMyProfile.bind(userController))

  // Cambiar contraseña
  fastify.put('/me/password', {
    schema: {
      tags: ['Users - Profile'],
      summary: 'Cambiar contraseña',
      body: {
        type: 'object',
        required: ['currentPassword', 'newPassword', 'confirmPassword'],
        properties: {
          currentPassword: { type: 'string', minLength: 1 },
          newPassword: { type: 'string', minLength: 8, maxLength: 128 },
          confirmPassword: { type: 'string', minLength: 1 }
        }
      }
    }
  }, userController.changePassword.bind(userController))

  // Actualizar configuraciones
  fastify.put('/me/settings', {
    schema: {
      tags: ['Users - Profile'],
      summary: 'Actualizar configuraciones de usuario',
      body: {
        type: 'object',
        properties: {
          language: { type: 'string', minLength: 2, maxLength: 2 },
          currency: { type: 'string', minLength: 3, maxLength: 3 },
          timezone: { type: 'string' },
          emailNotifications: { type: 'boolean' },
          smsNotifications: { type: 'boolean' },
          pushNotifications: { type: 'boolean' },
          marketingEmails: { type: 'boolean' },
          twoFactorEnabled: { type: 'boolean' }
        }
      }
    }
  }, userController.updateSettings.bind(userController))

  // Obtener mis referidos
  fastify.get('/me/referrals', {
    schema: {
      tags: ['Users - Profile'],
      summary: 'Obtener información de referidos'
    }
  }, userController.getMyReferrals.bind(userController))

  // Obtener estado de verificación
  fastify.get('/me/verification-status', {
    schema: {
      tags: ['Users - Profile'],
      summary: 'Obtener estado de verificación de cuenta'
    }
  }, userController.getVerificationStatus.bind(userController))

  // Eliminar mi cuenta
  fastify.delete('/me', {
    schema: {
      tags: ['Users - Profile'],
      summary: 'Eliminar mi cuenta',
      body: {
        type: 'object',
        properties: {
          reason: { type: 'string', maxLength: 500 }
        }
      }
    }
  }, userController.deleteMyAccount.bind(userController))

  // === RUTAS DE NOTIFICACIONES ===

  // Obtener mis notificaciones
  fastify.get('/notifications', {
    schema: {
      tags: ['Users - Notifications'],
      summary: 'Obtener mis notificaciones',
      querystring: {
        type: 'object',
        properties: {
          limit: { type: 'number', minimum: 1, maximum: 100, default: 20 },
          offset: { type: 'number', minimum: 0, default: 0 },
          unreadOnly: { type: 'boolean', default: false }
        }
      }
    }
  }, notificationController.getMyNotifications.bind(notificationController))

  // Marcar notificación como leída
  fastify.put('/notifications/:id/read', {
    schema: {
      tags: ['Users - Notifications'],
      summary: 'Marcar notificación como leída',
      params: {
        type: 'object',
        properties: {
          id: { type: 'string', format: 'uuid' }
        },
        required: ['id']
      }
    }
  }, notificationController.markAsRead.bind(notificationController))

  // Marcar todas las notificaciones como leídas
  fastify.put('/notifications/read-all', {
    schema: {
      tags: ['Users - Notifications'],
      summary: 'Marcar todas las notificaciones como leídas'
    }
  }, notificationController.markAllAsRead.bind(notificationController))

  // Eliminar notificación
  fastify.delete('/notifications/:id', {
    schema: {
      tags: ['Users - Notifications'],
      summary: 'Eliminar notificación',
      params: {
        type: 'object',
        properties: {
          id: { type: 'string', format: 'uuid' }
        },
        required: ['id']
      }
    }
  }, notificationController.deleteNotification.bind(notificationController))

  // === RUTAS ADMINISTRATIVAS ===

  // Listar usuarios (solo administradores)
  fastify.get('/', {
    preHandler: [requireRole(['ADMIN'])],
    schema: {
      tags: ['Users - Admin'],
      summary: 'Listar usuarios (solo administradores)',
      querystring: {
        type: 'object',
        properties: {
          status: { type: 'string', enum: ['ACTIVE', 'INACTIVE', 'SUSPENDED', 'BANNED', 'PENDING_VERIFICATION'] },
          role: { type: 'string' },
          country: { type: 'string' },
          registeredAfter: { type: 'string', format: 'date' },
          registeredBefore: { type: 'string', format: 'date' },
          hasVerifiedEmail: { type: 'boolean' },
          search: { type: 'string' },
          limit: { type: 'number', minimum: 1, maximum: 100, default: 20 },
          offset: { type: 'number', minimum: 0, default: 0 }
        }
      }
    }
  }, userController.listUsers.bind(userController))

  // Obtener estadísticas de usuarios
  fastify.get('/stats', {
    preHandler: [requireRole(['ADMIN'])],
    schema: {
      tags: ['Users - Admin'],
      summary: 'Obtener estadísticas de usuarios (solo administradores)'
    }
  }, userController.getUserStats.bind(userController))

  // Obtener usuario por ID (solo administradores)
  fastify.get('/:id', {
    preHandler: [requireRole(['ADMIN'])],
    schema: {
      tags: ['Users - Admin'],
      summary: 'Obtener usuario por ID (solo administradores)',
      params: {
        type: 'object',
        properties: {
          id: { type: 'string', format: 'uuid' }
        },
        required: ['id']
      },
      querystring: {
        type: 'object',
        properties: {
          includeProfile: { type: 'boolean', default: false }
        }
      }
    }
  }, userController.getUserById.bind(userController))

  // Actualizar estado de usuario (solo administradores)
  fastify.put('/:id/status', {
    preHandler: [requireRole(['ADMIN'])],
    schema: {
      tags: ['Users - Admin'],
      summary: 'Actualizar estado de usuario (solo administradores)',
      params: {
        type: 'object',
        properties: {
          id: { type: 'string', format: 'uuid' }
        },
        required: ['id']
      },
      body: {
        type: 'object',
        required: ['status'],
        properties: {
          status: { 
            type: 'string', 
            enum: ['ACTIVE', 'INACTIVE', 'SUSPENDED', 'BANNED', 'PENDING_VERIFICATION'] 
          },
          reason: { type: 'string', minLength: 10, maxLength: 500 }
        }
      }
    }
  }, userController.updateUserStatus.bind(userController))

  // Eliminar usuario (solo administradores)
  fastify.delete('/:id', {
    preHandler: [requireRole(['ADMIN'])],
    schema: {
      tags: ['Users - Admin'],
      summary: 'Eliminar usuario (solo administradores)',
      params: {
        type: 'object',
        properties: {
          id: { type: 'string', format: 'uuid' }
        },
        required: ['id']
      },
      body: {
        type: 'object',
        properties: {
          reason: { type: 'string', maxLength: 500 }
        }
      }
    }
  }, userController.deleteUser.bind(userController))

  // === RUTAS ADMINISTRATIVAS DE NOTIFICACIONES ===

  // Crear notificación (solo administradores)
  fastify.post('/notifications', {
    preHandler: [requireRole(['ADMIN'])],
    schema: {
      tags: ['Users - Admin Notifications'],
      summary: 'Crear notificación (solo administradores)',
      body: {
        type: 'object',
        required: ['userId', 'type', 'title', 'message'],
        properties: {
          userId: { type: 'string', format: 'uuid' },
          type: { type: 'string', enum: ['EMAIL', 'SMS', 'PUSH', 'IN_APP'] },
          title: { type: 'string', minLength: 1, maxLength: 100 },
          message: { type: 'string', minLength: 1, maxLength: 500 },
          data: { type: 'object' }
        }
      }
    }
  }, notificationController.createNotification.bind(notificationController))

  // Crear notificaciones masivas (solo administradores)
  fastify.post('/notifications/bulk', {
    preHandler: [requireRole(['ADMIN'])],
    schema: {
      tags: ['Users - Admin Notifications'],
      summary: 'Crear notificaciones masivas (solo administradores)',
      body: {
        type: 'object',
        required: ['userIds', 'title', 'message'],
        properties: {
          userIds: { 
            type: 'array',
            items: { type: 'string', format: 'uuid' },
            minItems: 1
          },
          title: { type: 'string', minLength: 1, maxLength: 100 },
          message: { type: 'string', minLength: 1, maxLength: 500 },
          type: { type: 'string', enum: ['EMAIL', 'SMS', 'PUSH', 'IN_APP'], default: 'IN_APP' },
          data: { type: 'object' }
        }
      }
    }
  }, notificationController.createBulkNotification.bind(notificationController))

  // Limpiar notificaciones antiguas (solo administradores)
  fastify.delete('/notifications/cleanup', {
    preHandler: [requireRole(['ADMIN'])],
    schema: {
      tags: ['Users - Admin Notifications'],
      summary: 'Limpiar notificaciones antiguas (solo administradores)',
      querystring: {
        type: 'object',
        properties: {
          daysOld: { type: 'number', minimum: 1, default: 30 }
        }
      }
    }
  }, notificationController.cleanupNotifications.bind(notificationController))
}