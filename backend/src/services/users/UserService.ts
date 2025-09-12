import { prisma } from '../../utils/database'
import { logger } from '../../utils/logger'
import { AppError, ErrorCode } from '../../types/errors'
import { hashPassword, comparePassword } from '../../utils/crypto'
import type { 
  UserResponse, 
  UserProfileResponse,
  UpdateUserProfileRequest,
  ChangePasswordRequest,
  UpdateUserSettingsRequest,
  CreateUserRequest,
  UserListResponse,
  UserSearchFilters,
  UserStats,
  ReferralInfo,
  UserStatus,
  UserRole,
  UserVerificationStatus
} from '../../types/users'

export class UserService {

  /**
   * Obtener usuario por ID
   */
  async getUserById(userId: string, includeProfile = false): Promise<UserResponse | UserProfileResponse> {
    try {
      const user = await prisma.user.findUnique({
        where: { id: userId },
        include: {
          country: true,
          timeZone: true,
          referredBy: {
            select: { id: true, username: true, firstName: true, lastName: true }
          },
          roles: true,
          ...(includeProfile && {
            userSettings: true,
            notifications: {
              where: { isRead: false },
              take: 10,
              orderBy: { createdAt: 'desc' }
            }
          })
        }
      })

      if (!user) {
        throw new AppError('Usuario no encontrado', ErrorCode.NOT_FOUND)
      }

      const formattedUser = this.formatUserResponse(user)

      if (includeProfile) {
        const stats = await this.getUserStats(userId)
        const settings = await this.getUserSettings(userId)
        const notificationSettings = await this.getNotificationSettings(userId)

        return {
          ...formattedUser,
          settings,
          stats,
          notifications: notificationSettings
        } as UserProfileResponse
      }

      return formattedUser
    } catch (error) {
      logger.error('Error obteniendo usuario:', error)
      throw error instanceof AppError ? error : new AppError('Error obteniendo usuario')
    }
  }

  /**
   * Actualizar perfil de usuario
   */
  async updateUserProfile(userId: string, updateData: UpdateUserProfileRequest): Promise<UserResponse> {
    try {
      const user = await prisma.user.update({
        where: { id: userId },
        data: {
          firstName: updateData.firstName,
          lastName: updateData.lastName,
          phoneNumber: updateData.phoneNumber,
          countryId: updateData.countryId,
          timeZoneId: updateData.timeZoneId,
          profilePhotoUrl: updateData.profilePhotoUrl,
          updatedAt: new Date()
        },
        include: {
          country: true,
          timeZone: true,
          referredBy: {
            select: { id: true, username: true, firstName: true, lastName: true }
          },
          roles: true
        }
      })

      logger.info(`Perfil actualizado para usuario: ${userId}`)
      return this.formatUserResponse(user)
    } catch (error) {
      logger.error('Error actualizando perfil:', error)
      throw error instanceof AppError ? error : new AppError('Error actualizando perfil')
    }
  }

  /**
   * Cambiar contraseña
   */
  async changePassword(userId: string, passwordData: ChangePasswordRequest): Promise<{ success: boolean }> {
    try {
      const user = await prisma.user.findUnique({
        where: { id: userId },
        select: { password: true }
      })

      if (!user) {
        throw new AppError('Usuario no encontrado', ErrorCode.NOT_FOUND)
      }

      // Verificar contraseña actual
      const isValidPassword = await comparePassword(passwordData.currentPassword, user.password)
      if (!isValidPassword) {
        throw new AppError('Contraseña actual incorrecta', ErrorCode.BAD_REQUEST)
      }

      // Encriptar nueva contraseña
      const hashedNewPassword = await hashPassword(passwordData.newPassword)

      await prisma.user.update({
        where: { id: userId },
        data: {
          password: hashedNewPassword,
          updatedAt: new Date()
        }
      })

      // Log de actividad de seguridad
      await this.logUserActivity(userId, 'PASSWORD_CHANGED', 'Contraseña cambiada exitosamente')

      logger.info(`Contraseña cambiada para usuario: ${userId}`)
      return { success: true }
    } catch (error) {
      logger.error('Error cambiando contraseña:', error)
      throw error instanceof AppError ? error : new AppError('Error cambiando contraseña')
    }
  }

  /**
   * Actualizar configuraciones de usuario
   */
  async updateUserSettings(userId: string, settings: UpdateUserSettingsRequest): Promise<{ success: boolean }> {
    try {
      await prisma.userSettings.upsert({
        where: { userId },
        update: {
          ...settings,
          updatedAt: new Date()
        },
        create: {
          userId,
          language: settings.language || 'es',
          currency: settings.currency || 'USD',
          timezone: settings.timezone || 'UTC',
          emailNotifications: settings.emailNotifications ?? true,
          smsNotifications: settings.smsNotifications ?? false,
          pushNotifications: settings.pushNotifications ?? true,
          marketingEmails: settings.marketingEmails ?? false,
          twoFactorEnabled: settings.twoFactorEnabled ?? false,
          createdAt: new Date()
        }
      })

      logger.info(`Configuraciones actualizadas para usuario: ${userId}`)
      return { success: true }
    } catch (error) {
      logger.error('Error actualizando configuraciones:', error)
      throw new AppError('Error actualizando configuraciones')
    }
  }

  /**
   * Listar usuarios con filtros
   */
  async listUsers(filters: UserSearchFilters = {}, limit = 20, offset = 0): Promise<UserListResponse> {
    try {
      const where: any = {}

      // Aplicar filtros
      if (filters.status) {
        where.status = filters.status
      }

      if (filters.country) {
        where.country = { code: filters.country }
      }

      if (filters.hasVerifiedEmail !== undefined) {
        where.emailVerifiedAt = filters.hasVerifiedEmail ? { not: null } : null
      }

      if (filters.registeredAfter) {
        where.createdAt = { ...where.createdAt, gte: filters.registeredAfter }
      }

      if (filters.registeredBefore) {
        where.createdAt = { ...where.createdAt, lte: filters.registeredBefore }
      }

      if (filters.search) {
        where.OR = [
          { username: { contains: filters.search, mode: 'insensitive' } },
          { email: { contains: filters.search, mode: 'insensitive' } },
          { firstName: { contains: filters.search, mode: 'insensitive' } },
          { lastName: { contains: filters.search, mode: 'insensitive' } }
        ]
      }

      const [users, total] = await Promise.all([
        prisma.user.findMany({
          where,
          include: {
            country: true,
            timeZone: true,
            referredBy: {
              select: { id: true, username: true, firstName: true, lastName: true }
            },
            roles: true
          },
          orderBy: { createdAt: 'desc' },
          take: limit,
          skip: offset
        }),
        prisma.user.count({ where })
      ])

      return {
        users: users.map(user => this.formatUserResponse(user)),
        total,
        page: Math.floor(offset / limit) + 1,
        totalPages: Math.ceil(total / limit)
      }
    } catch (error) {
      logger.error('Error listando usuarios:', error)
      throw new AppError('Error obteniendo usuarios')
    }
  }

  /**
   * Obtener información de referidos
   */
  async getReferralInfo(userId: string): Promise<ReferralInfo> {
    try {
      const user = await prisma.user.findUnique({
        where: { id: userId },
        select: { referralCode: true }
      })

      if (!user) {
        throw new AppError('Usuario no encontrado', ErrorCode.NOT_FOUND)
      }

      const referrals = await prisma.user.findMany({
        where: { referredByUserId: userId },
        select: {
          id: true,
          username: true,
          createdAt: true,
          status: true
        },
        orderBy: { createdAt: 'desc' }
      })

      const totalReferrals = referrals.length
      const activeReferrals = referrals.filter(r => r.status === 'ACTIVE').length

      // Calcular ganancias por referidos (esto se implementaría según el sistema de comisiones)
      const totalEarnings = 0 // Implementar lógica de cálculo de ganancias

      return {
        code: user.referralCode,
        totalReferrals,
        activeReferrals,
        totalEarnings,
        referrals: referrals.map(r => ({
          id: r.id,
          username: r.username,
          joinedAt: r.createdAt,
          isActive: r.status === 'ACTIVE',
          earnings: 0 // Implementar cálculo individual
        }))
      }
    } catch (error) {
      logger.error('Error obteniendo información de referidos:', error)
      throw error instanceof AppError ? error : new AppError('Error obteniendo información de referidos')
    }
  }

  /**
   * Obtener estado de verificación
   */
  async getVerificationStatus(userId: string): Promise<UserVerificationStatus> {
    try {
      const user = await prisma.user.findUnique({
        where: { id: userId },
        select: {
          emailVerifiedAt: true,
          phoneNumber: true,
          // Agregar más campos de verificación según necesidades
        }
      })

      if (!user) {
        throw new AppError('Usuario no encontrado', ErrorCode.NOT_FOUND)
      }

      const status: UserVerificationStatus = {
        email: !!user.emailVerifiedAt,
        phone: !!user.phoneNumber, // Implementar verificación de teléfono
        identity: false, // Implementar verificación de identidad
        address: false, // Implementar verificación de dirección
        paymentMethod: false, // Implementar verificación de método de pago
        completionLevel: 0
      }

      // Calcular nivel de completitud
      const verifications = Object.values(status).filter(v => typeof v === 'boolean')
      const completedVerifications = verifications.filter(v => v === true).length
      status.completionLevel = Math.round((completedVerifications / verifications.length) * 100)

      return status
    } catch (error) {
      logger.error('Error obteniendo estado de verificación:', error)
      throw error instanceof AppError ? error : new AppError('Error obteniendo estado de verificación')
    }
  }

  /**
   * Actualizar estado del usuario
   */
  async updateUserStatus(userId: string, status: UserStatus, reason?: string): Promise<UserResponse> {
    try {
      const user = await prisma.user.update({
        where: { id: userId },
        data: {
          status,
          updatedAt: new Date()
        },
        include: {
          country: true,
          timeZone: true,
          referredBy: {
            select: { id: true, username: true, firstName: true, lastName: true }
          },
          roles: true
        }
      })

      // Log de actividad administrativa
      await this.logUserActivity(userId, 'STATUS_CHANGED', `Estado cambiado a ${status}${reason ? `: ${reason}` : ''}`)

      logger.info(`Estado de usuario cambiado: ${userId} -> ${status}`)
      return this.formatUserResponse(user)
    } catch (error) {
      logger.error('Error actualizando estado de usuario:', error)
      throw error instanceof AppError ? error : new AppError('Error actualizando estado de usuario')
    }
  }

  /**
   * Eliminar usuario (soft delete)
   */
  async deleteUser(userId: string, reason?: string): Promise<{ success: boolean }> {
    try {
      await prisma.user.update({
        where: { id: userId },
        data: {
          status: 'INACTIVE' as UserStatus,
          deletedAt: new Date(),
          updatedAt: new Date()
        }
      })

      await this.logUserActivity(userId, 'ACCOUNT_DELETED', `Cuenta eliminada${reason ? `: ${reason}` : ''}`)

      logger.info(`Usuario eliminado: ${userId}`)
      return { success: true }
    } catch (error) {
      logger.error('Error eliminando usuario:', error)
      throw error instanceof AppError ? error : new AppError('Error eliminando usuario')
    }
  }

  /**
   * Obtener estadísticas del usuario
   */
  private async getUserStats(userId: string): Promise<UserStats> {
    try {
      // Implementar consultas para obtener estadísticas reales
      const stats: UserStats = {
        totalGamesPlayed: 0,
        totalWinnings: 0,
        totalDeposits: 0,
        totalWithdrawals: 0,
        accountAge: 0,
        lastActivity: new Date(),
        favoriteGames: []
      }

      // Aquí se implementarían las consultas reales a la base de datos
      // Por ejemplo:
      // - Contar juegos jugados desde tablas de bingo
      // - Sumar ganancias desde transacciones
      // - Calcular días desde creación de cuenta
      // etc.

      return stats
    } catch (error) {
      logger.error('Error obteniendo estadísticas de usuario:', error)
      return {
        totalGamesPlayed: 0,
        totalWinnings: 0,
        totalDeposits: 0,
        totalWithdrawals: 0,
        accountAge: 0,
        lastActivity: new Date(),
        favoriteGames: []
      }
    }
  }

  /**
   * Obtener configuraciones del usuario
   */
  private async getUserSettings(userId: string) {
    const settings = await prisma.userSettings.findUnique({
      where: { userId }
    })

    return settings || {
      language: 'es',
      currency: 'USD',
      timezone: 'UTC',
      emailNotifications: true,
      smsNotifications: false,
      pushNotifications: true,
      marketingEmails: false,
      twoFactorEnabled: false,
      privacyLevel: 'PUBLIC' as const
    }
  }

  /**
   * Obtener configuraciones de notificaciones
   */
  private async getNotificationSettings(userId: string) {
    const settings = await this.getUserSettings(userId)
    
    return {
      email: settings.emailNotifications,
      sms: settings.smsNotifications,
      push: settings.pushNotifications,
      gameUpdates: true,
      promotions: settings.marketingEmails,
      security: true,
      newsletter: settings.marketingEmails
    }
  }

  /**
   * Registrar actividad del usuario
   */
  private async logUserActivity(userId: string, action: string, description: string, metadata?: any) {
    try {
      await prisma.userActivityLog.create({
        data: {
          userId,
          action,
          description,
          ipAddress: '0.0.0.0', // Se obtendría del request
          userAgent: 'System',
          metadata: metadata || {},
          createdAt: new Date()
        }
      })
    } catch (error) {
      logger.error('Error registrando actividad de usuario:', error)
    }
  }

  /**
   * Formatear respuesta de usuario
   */
  private formatUserResponse(user: any): UserResponse {
    return {
      id: user.id,
      username: user.username,
      email: user.email,
      firstName: user.firstName,
      lastName: user.lastName,
      fullName: `${user.firstName} ${user.lastName}`.trim(),
      phoneNumber: user.phoneNumber,
      profilePhotoUrl: user.profilePhotoUrl,
      status: user.status as UserStatus,
      emailVerifiedAt: user.emailVerifiedAt,
      country: user.country ? {
        id: user.country.id,
        name: user.country.name,
        code: user.country.code,
        flag: user.country.flag
      } : undefined,
      timeZone: user.timeZone ? {
        id: user.timeZone.id,
        name: user.timeZone.name,
        offset: user.timeZone.offset,
        abbreviation: user.timeZone.abbreviation
      } : undefined,
      referralCode: user.referralCode,
      referredBy: user.referredBy ? {
        id: user.referredBy.id,
        username: user.referredBy.username,
        fullName: `${user.referredBy.firstName} ${user.referredBy.lastName}`.trim()
      } : undefined,
      roles: user.roles?.map((role: any) => role.name as UserRole) || [],
      createdAt: user.createdAt,
      updatedAt: user.updatedAt,
      lastLoginAt: user.lastLoginAt
    }
  }
}