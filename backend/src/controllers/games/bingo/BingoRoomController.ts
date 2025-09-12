import { FastifyRequest, FastifyReply } from 'fastify'
import { BingoRoomService } from '../../../services/games/bingo/BingoRoomService'
import { 
  CreateBingoRoomSchema, 
  UpdateBingoRoomSchema,
  BingoRoomType 
} from '../../../types/games/bingo'
import { AppError, ErrorCode } from '../../../types/errors'
import { logger } from '../../../utils/logger'

export class BingoRoomController {
  private bingoRoomService: BingoRoomService

  constructor() {
    this.bingoRoomService = new BingoRoomService()
  }

  /**
   * GET /api/bingo/rooms
   * Listar salas disponibles
   */
  async listRooms(request: FastifyRequest, reply: FastifyReply) {
    try {
      const query = request.query as any
      const filters = {
        roomType: query.roomType ? parseInt(query.roomType) : undefined,
        creatorId: query.creatorId,
        limit: query.limit ? parseInt(query.limit) : 20,
        offset: query.offset ? parseInt(query.offset) : 0
      }

      const userId = request.user?.id
      const result = await this.bingoRoomService.listAvailableRooms(userId, filters)

      return reply.status(200).send({
        success: true,
        data: result,
        message: 'Salas obtenidas exitosamente'
      })
    } catch (error) {
      logger.error('Error en listRooms:', error)
      return reply.status(500).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * GET /api/bingo/rooms/:id
   * Obtener sala por ID
   */
  async getRoomById(request: FastifyRequest, reply: FastifyReply) {
    try {
      const params = request.params as { id: string }
      const userId = request.user?.id

      const room = await this.bingoRoomService.getRoomById(params.id, userId)

      return reply.status(200).send({
        success: true,
        data: room,
        message: 'Sala obtenida exitosamente'
      })
    } catch (error) {
      logger.error('Error en getRoomById:', error)
      const statusCode = error instanceof AppError && error.code === ErrorCode.NOT_FOUND ? 404 : 500
      return reply.status(statusCode).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * GET /api/bingo/rooms/code/:encrypted
   * Obtener sala por código encriptado
   */
  async getRoomByCode(request: FastifyRequest, reply: FastifyReply) {
    try {
      const params = request.params as { encrypted: string }
      
      const room = await this.bingoRoomService.getRoomByEncrypted(params.encrypted)

      return reply.status(200).send({
        success: true,
        data: room,
        message: 'Sala obtenida exitosamente'
      })
    } catch (error) {
      logger.error('Error en getRoomByCode:', error)
      const statusCode = error instanceof AppError && error.code === ErrorCode.NOT_FOUND ? 404 : 500
      return reply.status(statusCode).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * POST /api/bingo/rooms
   * Crear nueva sala
   */
  async createRoom(request: FastifyRequest, reply: FastifyReply) {
    try {
      const body = request.body as any
      const userId = request.user!.id

      // Validar datos de entrada
      const validatedData = CreateBingoRoomSchema.parse(body)

      const room = await this.bingoRoomService.createRoom(validatedData, userId)

      return reply.status(201).send({
        success: true,
        data: room,
        message: 'Sala creada exitosamente'
      })
    } catch (error) {
      logger.error('Error en createRoom:', error)
      
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
   * PUT /api/bingo/rooms/:id
   * Actualizar sala
   */
  async updateRoom(request: FastifyRequest, reply: FastifyReply) {
    try {
      const params = request.params as { id: string }
      const body = request.body as any
      const userId = request.user!.id

      // Validar datos de entrada
      const validatedData = UpdateBingoRoomSchema.parse(body)

      const room = await this.bingoRoomService.updateRoom(params.id, validatedData, userId)

      return reply.status(200).send({
        success: true,
        data: room,
        message: 'Sala actualizada exitosamente'
      })
    } catch (error) {
      logger.error('Error en updateRoom:', error)
      
      if (error instanceof Error && error.name === 'ZodError') {
        return reply.status(400).send({
          success: false,
          message: 'Datos de entrada inválidos',
          errors: JSON.parse(error.message)
        })
      }

      const statusCode = error instanceof AppError ? 
        (error.code === ErrorCode.NOT_FOUND ? 404 : 
         error.code === ErrorCode.FORBIDDEN ? 403 : 500) : 500

      return reply.status(statusCode).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * POST /api/bingo/rooms/:id/join
   * Unirse a una sala
   */
  async joinRoom(request: FastifyRequest, reply: FastifyReply) {
    try {
      const params = request.params as { id: string }
      const userId = request.user!.id

      const result = await this.bingoRoomService.joinRoom(params.id, userId)

      return reply.status(200).send({
        success: result.success,
        message: result.message
      })
    } catch (error) {
      logger.error('Error en joinRoom:', error)
      
      const statusCode = error instanceof AppError ? 
        (error.code === ErrorCode.NOT_FOUND ? 404 : 
         error.code === ErrorCode.BAD_REQUEST ? 400 : 500) : 500

      return reply.status(statusCode).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * GET /api/bingo/rooms/my
   * Obtener mis salas creadas
   */
  async getMyRooms(request: FastifyRequest, reply: FastifyReply) {
    try {
      const userId = request.user!.id
      const query = request.query as any
      
      const filters = {
        creatorId: userId,
        limit: query.limit ? parseInt(query.limit) : 20,
        offset: query.offset ? parseInt(query.offset) : 0
      }

      const result = await this.bingoRoomService.listAvailableRooms(userId, filters)

      return reply.status(200).send({
        success: true,
        data: result,
        message: 'Mis salas obtenidas exitosamente'
      })
    } catch (error) {
      logger.error('Error en getMyRooms:', error)
      return reply.status(500).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * GET /api/bingo/room-types
   * Obtener tipos de sala disponibles
   */
  async getRoomTypes(request: FastifyRequest, reply: FastifyReply) {
    try {
      const roomTypes = [
        { id: BingoRoomType.NORMAL, name: 'Sala Normal', description: 'Sala estándar de bingo' },
        { id: BingoRoomType.VIP, name: 'Sala VIP', description: 'Sala exclusiva con mejores premios' },
        { id: BingoRoomType.PREMIUM, name: 'Sala Premium', description: 'Sala premium con características especiales' },
        { id: BingoRoomType.TOURNAMENT, name: 'Torneo', description: 'Sala de torneo competitivo' }
      ]

      return reply.status(200).send({
        success: true,
        data: roomTypes,
        message: 'Tipos de sala obtenidos exitosamente'
      })
    } catch (error) {
      logger.error('Error en getRoomTypes:', error)
      return reply.status(500).send({
        success: false,
        message: 'Error interno del servidor'
      })
    }
  }

  /**
   * DELETE /api/bingo/rooms/:id
   * Eliminar/cerrar sala (solo el creador)
   */
  async deleteRoom(request: FastifyRequest, reply: FastifyReply) {
    try {
      const params = request.params as { id: string }
      const userId = request.user!.id

      // Marcar sala como cerrada en lugar de eliminarla
      const updatedRoom = await this.bingoRoomService.updateRoom(
        params.id, 
        { closeRoom: true }, 
        userId
      )

      return reply.status(200).send({
        success: true,
        data: updatedRoom,
        message: 'Sala cerrada exitosamente'
      })
    } catch (error) {
      logger.error('Error en deleteRoom:', error)
      
      const statusCode = error instanceof AppError ? 
        (error.code === ErrorCode.NOT_FOUND ? 404 : 
         error.code === ErrorCode.FORBIDDEN ? 403 : 500) : 500

      return reply.status(statusCode).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }
}