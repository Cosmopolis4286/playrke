import { FastifyRequest, FastifyReply } from 'fastify'
import { UserService } from '../../services/users/UserService'
import { 
  UpdateUserProfileSchema, 
  ChangePasswordSchema, 
  UpdateUserSettingsSchema,
  UpdateUserStatusSchema
} from '../../types/users'
import { AppError, ErrorCode } from '../../types/errors'
import { logger } from '../../utils/logger'

export class UserController {
  private userService: UserService

  constructor() {
    this.userService = new UserService()
  }

  /**
   * GET /api/users/me
   * Obtener perfil del usuario actual
   */
  async getMyProfile(request: FastifyRequest, reply: FastifyReply) {
    try {
      const userId = request.user!.id
      const includeProfile = (request.query as any)?.includeProfile === 'true'

      const user = await this.userService.getUserById(userId, includeProfile)

      return reply.status(200).send({
        success: true,
        data: user,
        message: 'Perfil obtenido exitosamente'
      })
    } catch (error) {
      logger.error('Error en getMyProfile:', error)
      const statusCode = error instanceof AppError && error.code === ErrorCode.NOT_FOUND ? 404 : 500
      return reply.status(statusCode).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * PUT /api/users/me/profile
   * Actualizar perfil del usuario
   */
  async updateMyProfile(request: FastifyRequest, reply: FastifyReply) {
    try {
      const userId = request.user!.id
      const body = request.body as any

      // Validar datos de entrada
      const validatedData = UpdateUserProfileSchema.parse(body)

      const user = await this.userService.updateUserProfile(userId, validatedData)

      return reply.status(200).send({
        success: true,
        data: user,
        message: 'Perfil actualizado exitosamente'
      })
    } catch (error) {
      logger.error('Error en updateMyProfile:', error)
      
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
   * PUT /api/users/me/password
   * Cambiar contraseña
   */
  async changePassword(request: FastifyRequest, reply: FastifyReply) {
    try {
      const userId = request.user!.id
      const body = request.body as any

      // Validar datos de entrada
      const validatedData = ChangePasswordSchema.parse(body)

      const result = await this.userService.changePassword(userId, validatedData)

      return reply.status(200).send({
        success: result.success,
        message: 'Contraseña cambiada exitosamente'
      })
    } catch (error) {
      logger.error('Error en changePassword:', error)
      
      if (error instanceof Error && error.name === 'ZodError') {
        return reply.status(400).send({
          success: false,
          message: 'Datos de entrada inválidos',
          errors: JSON.parse(error.message)
        })
      }

      const statusCode = error instanceof AppError && error.code === ErrorCode.BAD_REQUEST ? 400 : 500
      return reply.status(statusCode).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * PUT /api/users/me/settings
   * Actualizar configuraciones del usuario
   */
  async updateSettings(request: FastifyRequest, reply: FastifyReply) {
    try {
      const userId = request.user!.id
      const body = request.body as any

      // Validar datos de entrada
      const validatedData = UpdateUserSettingsSchema.parse(body)

      const result = await this.userService.updateUserSettings(userId, validatedData)

      return reply.status(200).send({
        success: result.success,
        message: 'Configuraciones actualizadas exitosamente'
      })
    } catch (error) {
      logger.error('Error en updateSettings:', error)
      
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
   * GET /api/users/me/referrals
   * Obtener información de referidos
   */
  async getMyReferrals(request: FastifyRequest, reply: FastifyReply) {
    try {
      const userId = request.user!.id

      const referralInfo = await this.userService.getReferralInfo(userId)

      return reply.status(200).send({
        success: true,
        data: referralInfo,
        message: 'Información de referidos obtenida exitosamente'
      })
    } catch (error) {
      logger.error('Error en getMyReferrals:', error)
      const statusCode = error instanceof AppError && error.code === ErrorCode.NOT_FOUND ? 404 : 500
      return reply.status(statusCode).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * GET /api/users/me/verification-status
   * Obtener estado de verificación
   */
  async getVerificationStatus(request: FastifyRequest, reply: FastifyReply) {
    try {
      const userId = request.user!.id

      const status = await this.userService.getVerificationStatus(userId)

      return reply.status(200).send({
        success: true,
        data: status,
        message: 'Estado de verificación obtenido exitosamente'
      })
    } catch (error) {
      logger.error('Error en getVerificationStatus:', error)
      const statusCode = error instanceof AppError && error.code === ErrorCode.NOT_FOUND ? 404 : 500
      return reply.status(statusCode).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * DELETE /api/users/me
   * Eliminar mi cuenta
   */
  async deleteMyAccount(request: FastifyRequest, reply: FastifyReply) {
    try {
      const userId = request.user!.id
      const body = request.body as { reason?: string }

      const result = await this.userService.deleteUser(userId, body.reason)

      return reply.status(200).send({
        success: result.success,
        message: 'Cuenta eliminada exitosamente'
      })
    } catch (error) {
      logger.error('Error en deleteMyAccount:', error)
      return reply.status(500).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  // === ENDPOINTS ADMINISTRATIVOS ===

  /**
   * GET /api/users
   * Listar usuarios (solo administradores)
   */
  async listUsers(request: FastifyRequest, reply: FastifyReply) {
    try {
      const query = request.query as any

      const filters = {
        status: query.status,
        role: query.role,
        country: query.country,
        registeredAfter: query.registeredAfter ? new Date(query.registeredAfter) : undefined,
        registeredBefore: query.registeredBefore ? new Date(query.registeredBefore) : undefined,
        hasVerifiedEmail: query.hasVerifiedEmail === 'true' ? true : query.hasVerifiedEmail === 'false' ? false : undefined,
        search: query.search
      }

      const limit = query.limit ? parseInt(query.limit) : 20
      const offset = query.offset ? parseInt(query.offset) : 0

      const result = await this.userService.listUsers(filters, limit, offset)

      return reply.status(200).send({
        success: true,
        data: result,
        message: 'Usuarios obtenidos exitosamente'
      })
    } catch (error) {
      logger.error('Error en listUsers:', error)
      return reply.status(500).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * GET /api/users/:id
   * Obtener usuario por ID (solo administradores)
   */
  async getUserById(request: FastifyRequest, reply: FastifyReply) {
    try {
      const params = request.params as { id: string }
      const includeProfile = (request.query as any)?.includeProfile === 'true'

      const user = await this.userService.getUserById(params.id, includeProfile)

      return reply.status(200).send({
        success: true,
        data: user,
        message: 'Usuario obtenido exitosamente'
      })
    } catch (error) {
      logger.error('Error en getUserById:', error)
      const statusCode = error instanceof AppError && error.code === ErrorCode.NOT_FOUND ? 404 : 500
      return reply.status(statusCode).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * PUT /api/users/:id/status
   * Actualizar estado de usuario (solo administradores)
   */
  async updateUserStatus(request: FastifyRequest, reply: FastifyReply) {
    try {
      const params = request.params as { id: string }
      const body = request.body as any

      // Validar datos de entrada
      const validatedData = UpdateUserStatusSchema.parse(body)

      const user = await this.userService.updateUserStatus(params.id, validatedData.status, validatedData.reason)

      return reply.status(200).send({
        success: true,
        data: user,
        message: 'Estado de usuario actualizado exitosamente'
      })
    } catch (error) {
      logger.error('Error en updateUserStatus:', error)
      
      if (error instanceof Error && error.name === 'ZodError') {
        return reply.status(400).send({
          success: false,
          message: 'Datos de entrada inválidos',
          errors: JSON.parse(error.message)
        })
      }

      const statusCode = error instanceof AppError && error.code === ErrorCode.NOT_FOUND ? 404 : 500
      return reply.status(statusCode).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * DELETE /api/users/:id
   * Eliminar usuario (solo administradores)
   */
  async deleteUser(request: FastifyRequest, reply: FastifyReply) {
    try {
      const params = request.params as { id: string }
      const body = request.body as { reason?: string }

      const result = await this.userService.deleteUser(params.id, body.reason)

      return reply.status(200).send({
        success: result.success,
        message: 'Usuario eliminado exitosamente'
      })
    } catch (error) {
      logger.error('Error en deleteUser:', error)
      const statusCode = error instanceof AppError && error.code === ErrorCode.NOT_FOUND ? 404 : 500
      return reply.status(statusCode).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * GET /api/users/stats
   * Obtener estadísticas generales de usuarios
   */
  async getUserStats(request: FastifyRequest, reply: FastifyReply) {
    try {
      // Implementar estadísticas generales
      const stats = {
        totalUsers: 0,
        activeUsers: 0,
        newUsersToday: 0,
        verifiedUsers: 0,
        usersByCountry: [],
        usersByStatus: []
      }

      return reply.status(200).send({
        success: true,
        data: stats,
        message: 'Estadísticas obtenidas exitosamente'
      })
    } catch (error) {
      logger.error('Error en getUserStats:', error)
      return reply.status(500).send({
        success: false,
        message: 'Error interno del servidor'
      })
    }
  }
}