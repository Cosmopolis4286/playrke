import { FastifyRequest, FastifyReply } from 'fastify'
import { NotificationService } from '../../services/users/NotificationService'
import { CreateNotificationSchema } from '../../types/users'
import { AppError, ErrorCode } from '../../types/errors'
import { logger } from '../../utils/logger'

export class NotificationController {
  private notificationService: NotificationService

  constructor() {
    this.notificationService = new NotificationService()
  }

  /**
   * GET /api/notifications
   * Obtener mis notificaciones
   */
  async getMyNotifications(request: FastifyRequest, reply: FastifyReply) {
    try {
      const userId = request.user!.id
      const query = request.query as any

      const limit = query.limit ? parseInt(query.limit) : 20
      const offset = query.offset ? parseInt(query.offset) : 0
      const unreadOnly = query.unreadOnly === 'true'

      const result = await this.notificationService.getUserNotifications(userId, limit, offset, unreadOnly)

      return reply.status(200).send({
        success: true,
        data: result,
        message: 'Notificaciones obtenidas exitosamente'
      })
    } catch (error) {
      logger.error('Error en getMyNotifications:', error)
      return reply.status(500).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * PUT /api/notifications/:id/read
   * Marcar notificación como leída
   */
  async markAsRead(request: FastifyRequest, reply: FastifyReply) {
    try {
      const params = request.params as { id: string }
      const userId = request.user!.id

      const notification = await this.notificationService.markAsRead(params.id, userId)

      return reply.status(200).send({
        success: true,
        data: notification,
        message: 'Notificación marcada como leída'
      })
    } catch (error) {
      logger.error('Error en markAsRead:', error)
      return reply.status(500).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * PUT /api/notifications/read-all
   * Marcar todas las notificaciones como leídas
   */
  async markAllAsRead(request: FastifyRequest, reply: FastifyReply) {
    try {
      const userId = request.user!.id

      const result = await this.notificationService.markAllAsRead(userId)

      return reply.status(200).send({
        success: result.success,
        data: { updatedCount: result.updatedCount },
        message: `${result.updatedCount} notificaciones marcadas como leídas`
      })
    } catch (error) {
      logger.error('Error en markAllAsRead:', error)
      return reply.status(500).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * DELETE /api/notifications/:id
   * Eliminar notificación
   */
  async deleteNotification(request: FastifyRequest, reply: FastifyReply) {
    try {
      const params = request.params as { id: string }
      const userId = request.user!.id

      const result = await this.notificationService.deleteNotification(params.id, userId)

      return reply.status(200).send({
        success: result.success,
        message: 'Notificación eliminada exitosamente'
      })
    } catch (error) {
      logger.error('Error en deleteNotification:', error)
      return reply.status(500).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  // === ENDPOINTS ADMINISTRATIVOS ===

  /**
   * POST /api/notifications
   * Crear notificación (solo administradores)
   */
  async createNotification(request: FastifyRequest, reply: FastifyReply) {
    try {
      const body = request.body as any

      // Validar datos de entrada
      const validatedData = CreateNotificationSchema.parse(body)

      const notification = await this.notificationService.createNotification(validatedData)

      return reply.status(201).send({
        success: true,
        data: notification,
        message: 'Notificación creada exitosamente'
      })
    } catch (error) {
      logger.error('Error en createNotification:', error)
      
      if (error instanceof Error && error.name === 'ZodError') {
        return reply.status(400).send({
          success: false,
          message: 'Datos de entrada inválidos',
          errors: JSON.parse(error.message)
        })
      }

      return reply.status(500).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * POST /api/notifications/bulk
   * Crear notificaciones masivas (solo administradores)
   */
  async createBulkNotification(request: FastifyRequest, reply: FastifyReply) {
    try {
      const body = request.body as {
        userIds: string[]
        title: string
        message: string
        type?: 'EMAIL' | 'SMS' | 'PUSH' | 'IN_APP'
        data?: Record<string, any>
      }

      if (!body.userIds || !Array.isArray(body.userIds) || body.userIds.length === 0) {
        return reply.status(400).send({
          success: false,
          message: 'userIds es requerido y debe ser un array no vacío'
        })
      }

      if (!body.title || !body.message) {
        return reply.status(400).send({
          success: false,
          message: 'title y message son requeridos'
        })
      }

      const result = await this.notificationService.createBulkNotification(
        body.userIds,
        body.title,
        body.message,
        body.type || 'IN_APP',
        body.data
      )

      return reply.status(201).send({
        success: result.success,
        data: { createdCount: result.createdCount },
        message: `${result.createdCount} notificaciones creadas exitosamente`
      })
    } catch (error) {
      logger.error('Error en createBulkNotification:', error)
      return reply.status(500).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * DELETE /api/notifications/cleanup
   * Limpiar notificaciones antiguas (solo administradores)
   */
  async cleanupNotifications(request: FastifyRequest, reply: FastifyReply) {
    try {
      const query = request.query as { daysOld?: string }
      const daysOld = query.daysOld ? parseInt(query.daysOld) : 30

      const result = await this.notificationService.cleanupOldNotifications(daysOld)

      return reply.status(200).send({
        success: result.success,
        data: { deletedCount: result.deletedCount },
        message: `${result.deletedCount} notificaciones antiguas eliminadas`
      })
    } catch (error) {
      logger.error('Error en cleanupNotifications:', error)
      return reply.status(500).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }
}