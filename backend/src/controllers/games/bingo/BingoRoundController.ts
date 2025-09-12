import { FastifyRequest, FastifyReply } from 'fastify'
import { BingoRoundService } from '../../../services/games/bingo/BingoRoundService'
import { 
  CreateBingoRoundSchema, 
  DrawNumberSchema,
  BingoPrizeType 
} from '../../../types/games/bingo'
import { AppError, ErrorCode } from '../../../types/errors'
import { logger } from '../../../utils/logger'

export class BingoRoundController {
  private bingoRoundService: BingoRoundService

  constructor() {
    this.bingoRoundService = new BingoRoundService()
  }

  /**
   * POST /api/bingo/rounds
   * Crear nueva ronda
   */
  async createRound(request: FastifyRequest, reply: FastifyReply) {
    try {
      const body = request.body as any
      
      // Validar datos de entrada
      const validatedData = CreateBingoRoundSchema.parse(body)

      const round = await this.bingoRoundService.createRound(validatedData)

      return reply.status(201).send({
        success: true,
        data: round,
        message: 'Ronda creada exitosamente'
      })
    } catch (error) {
      logger.error('Error en createRound:', error)
      
      if (error instanceof Error && error.name === 'ZodError') {
        return reply.status(400).send({
          success: false,
          message: 'Datos de entrada inválidos',
          errors: JSON.parse(error.message)
        })
      }

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
   * PUT /api/bingo/rounds/:id/start
   * Iniciar ronda
   */
  async startRound(request: FastifyRequest, reply: FastifyReply) {
    try {
      const params = request.params as { id: string }
      
      const round = await this.bingoRoundService.startRound(params.id)

      return reply.status(200).send({
        success: true,
        data: round,
        message: 'Ronda iniciada exitosamente'
      })
    } catch (error) {
      logger.error('Error en startRound:', error)
      
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
   * POST /api/bingo/rounds/:id/draw
   * Sacar número
   */
  async drawNumber(request: FastifyRequest, reply: FastifyReply) {
    try {
      const params = request.params as { id: string }
      const body = request.body as any
      
      // Validar datos de entrada
      const validatedData = DrawNumberSchema.parse({
        roundId: params.id,
        roomId: body.roomId,
        number: body.number
      })

      const draw = await this.bingoRoundService.drawNumber(validatedData)

      return reply.status(201).send({
        success: true,
        data: draw,
        message: `Número ${draw.letter}${draw.number} sacado exitosamente`
      })
    } catch (error) {
      logger.error('Error en drawNumber:', error)
      
      if (error instanceof Error && error.name === 'ZodError') {
        return reply.status(400).send({
          success: false,
          message: 'Datos de entrada inválidos',
          errors: JSON.parse(error.message)
        })
      }

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
   * POST /api/bingo/rounds/:id/claim
   * Reclamar BINGO
   */
  async claimBingo(request: FastifyRequest, reply: FastifyReply) {
    try {
      const params = request.params as { id: string }
      const body = request.body as any
      const userId = request.user!.id
      
      const { cardId, claimedPattern } = body

      if (!cardId) {
        return reply.status(400).send({
          success: false,
          message: 'cardId es requerido'
        })
      }

      const result = await this.bingoRoundService.processBingoClaim(
        params.id, 
        userId, 
        cardId, 
        claimedPattern
      )

      if (result.isValid && result.winner) {
        return reply.status(200).send({
          success: true,
          data: result.winner,
          message: `¡BINGO válido! Premio de ${result.winner.prizeAmount} acreditado`
        })
      } else {
        return reply.status(400).send({
          success: false,
          message: 'BINGO no válido'
        })
      }
    } catch (error) {
      logger.error('Error en claimBingo:', error)
      
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
   * PUT /api/bingo/rounds/:id/end
   * Finalizar ronda
   */
  async endRound(request: FastifyRequest, reply: FastifyReply) {
    try {
      const params = request.params as { id: string }
      
      const round = await this.bingoRoundService.endRound(params.id)

      return reply.status(200).send({
        success: true,
        data: round,
        message: 'Ronda finalizada exitosamente'
      })
    } catch (error) {
      logger.error('Error en endRound:', error)
      
      const statusCode = error instanceof AppError ? 
        (error.code === ErrorCode.NOT_FOUND ? 404 : 500) : 500

      return reply.status(statusCode).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * GET /api/bingo/rooms/:roomId/current-round
   * Obtener ronda actual de una sala
   */
  async getCurrentRound(request: FastifyRequest, reply: FastifyReply) {
    try {
      const params = request.params as { roomId: string }
      
      const round = await this.bingoRoundService.getCurrentRound(params.roomId)

      if (!round) {
        return reply.status(404).send({
          success: false,
          message: 'No hay ronda activa en esta sala'
        })
      }

      return reply.status(200).send({
        success: true,
        data: round,
        message: 'Ronda actual obtenida exitosamente'
      })
    } catch (error) {
      logger.error('Error en getCurrentRound:', error)
      return reply.status(500).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * GET /api/bingo/rooms/:roomId/rounds/history
   * Obtener historial de rondas
   */
  async getRoundsHistory(request: FastifyRequest, reply: FastifyReply) {
    try {
      const params = request.params as { roomId: string }
      const query = request.query as any
      
      const limit = query.limit ? parseInt(query.limit) : 10

      const rounds = await this.bingoRoundService.getRoundsHistory(params.roomId, limit)

      return reply.status(200).send({
        success: true,
        data: rounds,
        message: 'Historial de rondas obtenido exitosamente'
      })
    } catch (error) {
      logger.error('Error en getRoundsHistory:', error)
      return reply.status(500).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * GET /api/bingo/rounds/:id
   * Obtener información de una ronda específica
   */
  async getRoundById(request: FastifyRequest, reply: FastifyReply) {
    try {
      const params = request.params as { id: string }
      
      // Por ahora retornamos la ronda actual, en el futuro se puede implementar búsqueda por ID
      const round = await this.bingoRoundService.getCurrentRound(params.id)

      if (!round) {
        return reply.status(404).send({
          success: false,
          message: 'Ronda no encontrada'
        })
      }

      return reply.status(200).send({
        success: true,
        data: round,
        message: 'Ronda obtenida exitosamente'
      })
    } catch (error) {
      logger.error('Error en getRoundById:', error)
      return reply.status(500).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * GET /api/bingo/rounds/:id/stats
   * Obtener estadísticas de una ronda
   */
  async getRoundStats(request: FastifyRequest, reply: FastifyReply) {
    try {
      const params = request.params as { id: string }
      
      // Obtener estadísticas básicas de la ronda
      // Esta funcionalidad se puede expandir según las necesidades
      
      return reply.status(200).send({
        success: true,
        data: {
          totalDraws: 0,
          totalWinners: 0,
          prizeDistribution: {
            fullHouse: 0,
            serial: 0,
            pattern: 0
          },
          averageGameTime: 0
        },
        message: 'Estadísticas de ronda obtenidas exitosamente'
      })
    } catch (error) {
      logger.error('Error en getRoundStats:', error)
      return reply.status(500).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * GET /api/bingo/prize-types
   * Obtener tipos de premio disponibles
   */
  async getPrizeTypes(request: FastifyRequest, reply: FastifyReply) {
    try {
      const prizeTypes = [
        { 
          id: BingoPrizeType.FULL_HOUSE, 
          name: 'Full House', 
          description: 'Cartón completo marcado',
          multiplier: 0.6 
        },
        { 
          id: BingoPrizeType.SERIAL, 
          name: 'Serial', 
          description: 'Línea completa (horizontal, vertical o diagonal)',
          multiplier: 0.3 
        },
        { 
          id: BingoPrizeType.PATTERN, 
          name: 'Patrón', 
          description: 'Patrón específico completado',
          multiplier: 0.1 
        }
      ]

      return reply.status(200).send({
        success: true,
        data: prizeTypes,
        message: 'Tipos de premio obtenidos exitosamente'
      })
    } catch (error) {
      logger.error('Error en getPrizeTypes:', error)
      return reply.status(500).send({
        success: false,
        message: 'Error interno del servidor'
      })
    }
  }
}