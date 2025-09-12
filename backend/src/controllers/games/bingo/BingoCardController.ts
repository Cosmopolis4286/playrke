import { FastifyRequest, FastifyReply } from 'fastify'
import { BingoCardService } from '../../../services/games/bingo/BingoCardService'
import { BuyCardsSchema } from '../../../types/games/bingo'
import { AppError, ErrorCode } from '../../../types/errors'
import { logger } from '../../../utils/logger'

export class BingoCardController {
  private bingoCardService: BingoCardService

  constructor() {
    this.bingoCardService = new BingoCardService()
  }

  /**
   * POST /api/bingo/cards/buy
   * Comprar cartones para una sala
   */
  async buyCards(request: FastifyRequest, reply: FastifyReply) {
    try {
      const body = request.body as any
      const userId = request.user!.id

      // Validar datos de entrada
      const validatedData = BuyCardsSchema.parse(body)

      const result = await this.bingoCardService.buyCards(validatedData, userId)

      return reply.status(201).send({
        success: true,
        data: {
          cards: result.cards,
          transaction: {
            id: result.transaction.id,
            amount: result.transaction.amount,
            currency: result.transaction.currencyId,
            status: result.transaction.status,
            reference: result.transaction.reference
          }
        },
        message: `${result.cards.length} cartones comprados exitosamente`
      })
    } catch (error) {
      logger.error('Error en buyCards:', error)
      
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
   * GET /api/bingo/rooms/:roomId/cards
   * Obtener mis cartones en una sala específica
   */
  async getMyCardsInRoom(request: FastifyRequest, reply: FastifyReply) {
    try {
      const params = request.params as { roomId: string }
      const userId = request.user!.id

      const cards = await this.bingoCardService.getUserCards(params.roomId, userId)

      return reply.status(200).send({
        success: true,
        data: cards,
        message: 'Cartones obtenidos exitosamente'
      })
    } catch (error) {
      logger.error('Error en getMyCardsInRoom:', error)
      return reply.status(500).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * GET /api/bingo/cards/:id
   * Obtener cartón específico por ID
   */
  async getCardById(request: FastifyRequest, reply: FastifyReply) {
    try {
      const params = request.params as { id: string }
      
      const card = await this.bingoCardService.getCardById(params.id)

      return reply.status(200).send({
        success: true,
        data: card,
        message: 'Cartón obtenido exitosamente'
      })
    } catch (error) {
      logger.error('Error en getCardById:', error)
      const statusCode = error instanceof AppError && error.code === ErrorCode.NOT_FOUND ? 404 : 500
      return reply.status(statusCode).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * POST /api/bingo/cards/:id/validate
   * Validar BINGO en un cartón
   */
  async validateBingo(request: FastifyRequest, reply: FastifyReply) {
    try {
      const params = request.params as { id: string }
      const body = request.body as any
      
      const { drawnNumbers, claimedPattern } = body

      if (!Array.isArray(drawnNumbers)) {
        return reply.status(400).send({
          success: false,
          message: 'drawnNumbers debe ser un array'
        })
      }

      const validation = await this.bingoCardService.validateBingo(
        params.id, 
        drawnNumbers, 
        claimedPattern
      )

      return reply.status(200).send({
        success: true,
        data: validation,
        message: validation.isValid ? 'BINGO válido' : 'BINGO no válido'
      })
    } catch (error) {
      logger.error('Error en validateBingo:', error)
      const statusCode = error instanceof AppError && error.code === ErrorCode.NOT_FOUND ? 404 : 500
      return reply.status(statusCode).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * GET /api/bingo/cards/my
   * Obtener todos mis cartones activos
   */
  async getMyCards(request: FastifyRequest, reply: FastifyReply) {
    try {
      const userId = request.user!.id
      const query = request.query as any
      
      // Por simplicidad, por ahora solo obtenemos todos los cartones activos del usuario
      // En el futuro se puede implementar paginación y filtros
      
      return reply.status(200).send({
        success: true,
        data: [], // Implementar lógica para obtener todos los cartones del usuario
        message: 'Cartones obtenidos exitosamente'
      })
    } catch (error) {
      logger.error('Error en getMyCards:', error)
      return reply.status(500).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * PUT /api/bingo/cards/:id/deactivate
   * Desactivar cartón (si está permitido)
   */
  async deactivateCard(request: FastifyRequest, reply: FastifyReply) {
    try {
      const params = request.params as { id: string }
      const userId = request.user!.id

      // Verificar que el cartón pertenece al usuario
      const card = await this.bingoCardService.getCardById(params.id)
      
      if (card.userId !== userId) {
        return reply.status(403).send({
          success: false,
          message: 'No tienes permisos para modificar este cartón'
        })
      }

      // Por ahora solo registramos la acción, en el futuro se implementará la lógica completa
      logger.info(`Intento de desactivar cartón: ${params.id}`, { userId })

      return reply.status(200).send({
        success: false,
        message: 'Función no disponible durante el juego'
      })
    } catch (error) {
      logger.error('Error en deactivateCard:', error)
      const statusCode = error instanceof AppError && error.code === ErrorCode.NOT_FOUND ? 404 : 500
      return reply.status(statusCode).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }

  /**
   * GET /api/bingo/rooms/:roomId/cards/stats
   * Estadísticas de cartones en una sala
   */
  async getCardStats(request: FastifyRequest, reply: FastifyReply) {
    try {
      const params = request.params as { roomId: string }
      
      // Obtener estadísticas básicas de la sala
      // Esta funcionalidad se puede expandir según las necesidades
      
      return reply.status(200).send({
        success: true,
        data: {
          totalCards: 0,
          activeCards: 0,
          soldCards: 0,
          availableCards: 0
        },
        message: 'Estadísticas obtenidas exitosamente'
      })
    } catch (error) {
      logger.error('Error en getCardStats:', error)
      return reply.status(500).send({
        success: false,
        message: error instanceof AppError ? error.message : 'Error interno del servidor'
      })
    }
  }
}