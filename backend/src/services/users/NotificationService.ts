import { prisma } from '../../utils/database'
import { logger } from '../../utils/logger'
import { AppError, ErrorCode } from '../../types/errors'
import type { 
  NotificationResponse,
  CreateNotificationRequest,
  NotificationType
} from '../../types/users'

export class NotificationService {

  /**
   * Crear nueva notificación
   */
  async createNotification(request: CreateNotificationRequest): Promise<NotificationResponse> {
    try {
      const notification = await prisma.notification.create({
        data: {
          userId: request.userId,
          type: request.type,
          title: request.title,
          message: request.message,
          data: request.data || {},
          isRead: false,
          createdAt: new Date()
        }
      })

      // Enviar notificación según el tipo
      await this.sendNotification(notification)

      logger.info(`Notificación creada: ${notification.id}`, {
        userId: request.userId,
        type: request.type,
        title: request.title
      })

      return this.formatNotificationResponse(notification)
    } catch (error) {
      logger.error('Error creando notificación:', error)
      throw error instanceof AppError ? error : new AppError('Error creando notificación')
    }
  }

  /**
   * Obtener notificaciones del usuario
   */
  async getUserNotifications(userId: string, limit = 20, offset = 0, unreadOnly = false): Promise<{ notifications: NotificationResponse[], total: number, unreadCount: number }> {
    try {
      const where: any = { userId }
      if (unreadOnly) {
        where.isRead = false
      }

      const [notifications, total, unreadCount] = await Promise.all([
        prisma.notification.findMany({
          where,
          orderBy: { createdAt: 'desc' },
          take: limit,
          skip: offset
        }),
        prisma.notification.count({ where }),
        prisma.notification.count({
          where: { userId, isRead: false }
        })
      ])

      return {
        notifications: notifications.map(n => this.formatNotificationResponse(n)),
        total,
        unreadCount
      }
    } catch (error) {
      logger.error('Error obteniendo notificaciones:', error)
      throw new AppError('Error obteniendo notificaciones')
    }
  }

  /**
   * Marcar notificación como leída
   */
  async markAsRead(notificationId: string, userId: string): Promise<NotificationResponse> {
    try {
      const notification = await prisma.notification.update({
        where: {
          id: notificationId,
          userId // Asegurar que pertenece al usuario
        },
        data: {
          isRead: true,
          readAt: new Date()
        }
      })

      return this.formatNotificationResponse(notification)
    } catch (error) {
      logger.error('Error marcando notificación como leída:', error)
      throw error instanceof AppError ? error : new AppError('Error actualizando notificación')
    }
  }

  /**
   * Marcar todas las notificaciones como leídas
   */
  async markAllAsRead(userId: string): Promise<{ success: boolean, updatedCount: number }> {
    try {
      const result = await prisma.notification.updateMany({
        where: {
          userId,
          isRead: false
        },
        data: {
          isRead: true,
          readAt: new Date()
        }
      })

      logger.info(`Marcadas ${result.count} notificaciones como leídas para usuario ${userId}`)

      return { success: true, updatedCount: result.count }
    } catch (error) {
      logger.error('Error marcando todas las notificaciones como leídas:', error)
      throw new AppError('Error actualizando notificaciones')
    }
  }

  /**
   * Eliminar notificación
   */
  async deleteNotification(notificationId: string, userId: string): Promise<{ success: boolean }> {
    try {
      await prisma.notification.delete({
        where: {
          id: notificationId,
          userId // Asegurar que pertenece al usuario
        }
      })

      logger.info(`Notificación eliminada: ${notificationId}`)
      return { success: true }
    } catch (error) {
      logger.error('Error eliminando notificación:', error)
      throw error instanceof AppError ? error : new AppError('Error eliminando notificación')
    }
  }

  /**
   * Crear notificación de sistema para múltiples usuarios
   */
  async createBulkNotification(userIds: string[], title: string, message: string, type: NotificationType = 'IN_APP', data?: Record<string, any>): Promise<{ success: boolean, createdCount: number }> {
    try {
      const notifications = userIds.map(userId => ({
        userId,
        type,
        title,
        message,
        data: data || {},
        isRead: false,
        createdAt: new Date()
      }))

      const result = await prisma.notification.createMany({
        data: notifications
      })

      logger.info(`Creadas ${result.count} notificaciones masivas`, {
        userCount: userIds.length,
        type,
        title
      })

      return { success: true, createdCount: result.count }
    } catch (error) {
      logger.error('Error creando notificaciones masivas:', error)
      throw new AppError('Error creando notificaciones masivas')
    }
  }

  /**
   * Limpiar notificaciones antiguas
   */
  async cleanupOldNotifications(daysOld = 30): Promise<{ success: boolean, deletedCount: number }> {
    try {
      const cutoffDate = new Date()
      cutoffDate.setDate(cutoffDate.getDate() - daysOld)

      const result = await prisma.notification.deleteMany({
        where: {
          createdAt: { lt: cutoffDate },
          isRead: true
        }
      })

      logger.info(`Eliminadas ${result.count} notificaciones antiguas`)
      return { success: true, deletedCount: result.count }
    } catch (error) {
      logger.error('Error limpiando notificaciones antiguas:', error)
      throw new AppError('Error limpiando notificaciones')
    }
  }

  /**
   * Enviar notificación según el tipo
   */
  private async sendNotification(notification: any): Promise<void> {
    try {
      switch (notification.type) {
        case 'EMAIL':
          await this.sendEmailNotification(notification)
          break
        case 'SMS':
          await this.sendSMSNotification(notification)
          break
        case 'PUSH':
          await this.sendPushNotification(notification)
          break
        case 'IN_APP':
          // Ya está guardada en BD, no requiere acción adicional
          break
        default:
          logger.warn(`Tipo de notificación no soportado: ${notification.type}`)
      }
    } catch (error) {
      logger.error('Error enviando notificación:', error)
      // No lanzar error para no interrumpir la creación de la notificación
    }
  }

  /**
   * Enviar notificación por email
   */
  private async sendEmailNotification(notification: any): Promise<void> {
    try {
      // Implementar envío de email
      // Por ahora solo logueamos
      logger.info('Enviando notificación por email', {
        userId: notification.userId,
        title: notification.title
      })
    } catch (error) {
      logger.error('Error enviando email:', error)
    }
  }

  /**
   * Enviar notificación por SMS
   */
  private async sendSMSNotification(notification: any): Promise<void> {
    try {
      // Implementar envío de SMS
      // Por ahora solo logueamos
      logger.info('Enviando notificación por SMS', {
        userId: notification.userId,
        title: notification.title
      })
    } catch (error) {
      logger.error('Error enviando SMS:', error)
    }
  }

  /**
   * Enviar notificación push
   */
  private async sendPushNotification(notification: any): Promise<void> {
    try {
      // Implementar envío de push notification
      // Por ahora solo logueamos
      logger.info('Enviando notificación push', {
        userId: notification.userId,
        title: notification.title
      })
    } catch (error) {
      logger.error('Error enviando push notification:', error)
    }
  }

  /**
   * Formatear respuesta de notificación
   */
  private formatNotificationResponse(notification: any): NotificationResponse {
    return {
      id: notification.id,
      userId: notification.userId,
      type: notification.type as NotificationType,
      title: notification.title,
      message: notification.message,
      data: notification.data,
      isRead: notification.isRead,
      createdAt: notification.createdAt,
      readAt: notification.readAt
    }
  }
}