import { prisma } from '../../../utils/database'
import { logger } from '../../../utils/logger'
import { AppError, ErrorCode } from '../../../types/errors'
import type { 
  BingoRoundResponse, 
  CreateBingoRoundRequest, 
  BingoRoundStatus,
  BingoPrizeType,
  DrawNumberRequest,
  BingoDrawResponse,
  BingoWinnerResponse
} from '../../../types/games/bingo'

export class BingoRoundService {

  /**
   * Crear nueva ronda de bingo
   */
  async createRound(request: CreateBingoRoundRequest): Promise<BingoRoundResponse> {
    try {
      // Validar que la sala existe y puede tener una nueva ronda
      const room = await prisma.bingoRoom.findUnique({
        where: { id: request.roomId },
        include: {
          _count: { select: { rounds: true } }
        }
      })

      if (!room) {
        throw new AppError('Sala no encontrada', ErrorCode.NOT_FOUND)
      }

      if (room.status !== 1) {
        throw new AppError('La sala no está activa', ErrorCode.BAD_REQUEST)
      }

      // Verificar que no hay rondas activas
      const activeRound = await prisma.bingoRound.findFirst({
        where: {
          roomId: request.roomId,
          status: { in: ['WAITING', 'IN_PROGRESS'] }
        }
      })

      if (activeRound) {
        throw new AppError('Ya existe una ronda activa en esta sala', ErrorCode.BAD_REQUEST)
      }

      const round = await prisma.bingoRound.create({
        data: {
          roomId: request.roomId,
          round: request.round,
          bingoPatternId: request.bingoPatternId,
          prizeType: request.prizeType,
          prizeValue: request.prizeValue,
          status: 'WAITING' as BingoRoundStatus
        },
        include: {
          room: true,
          pattern: true,
          draws: {
            orderBy: { drawnAt: 'asc' }
          },
          winners: {
            include: {
              user: {
                select: { id: true, username: true }
              },
              card: {
                select: { id: true, serialNumber: true }
              }
            }
          }
        }
      })

      logger.info(`Ronda de bingo creada: ${round.id}`, {
        roomId: request.roomId,
        round: request.round,
        prizeType: request.prizeType
      })

      return this.formatRoundResponse(round)
    } catch (error) {
      logger.error('Error creando ronda de bingo:', error)
      throw error instanceof AppError ? error : new AppError('Error creando ronda de bingo')
    }
  }

  /**
   * Iniciar ronda
   */
  async startRound(roundId: string): Promise<BingoRoundResponse> {
    try {
      const round = await prisma.bingoRound.findUnique({
        where: { id: roundId },
        include: { room: true }
      })

      if (!round) {
        throw new AppError('Ronda no encontrada', ErrorCode.NOT_FOUND)
      }

      if (round.status !== 'WAITING') {
        throw new AppError('La ronda no puede ser iniciada', ErrorCode.BAD_REQUEST)
      }

      const updatedRound = await prisma.bingoRound.update({
        where: { id: roundId },
        data: {
          status: 'IN_PROGRESS' as BingoRoundStatus,
          startTime: new Date()
        },
        include: {
          room: true,
          pattern: true,
          draws: {
            orderBy: { drawnAt: 'asc' }
          },
          winners: {
            include: {
              user: {
                select: { id: true, username: true }
              },
              card: {
                select: { id: true, serialNumber: true }
              }
            }
          }
        }
      })

      logger.info(`Ronda de bingo iniciada: ${roundId}`)

      return this.formatRoundResponse(updatedRound)
    } catch (error) {
      logger.error('Error iniciando ronda:', error)
      throw error instanceof AppError ? error : new AppError('Error iniciando ronda')
    }
  }

  /**
   * Sacar número
   */
  async drawNumber(request: DrawNumberRequest): Promise<BingoDrawResponse> {
    try {
      const round = await prisma.bingoRound.findUnique({
        where: { id: request.roundId },
        include: {
          draws: true
        }
      })

      if (!round) {
        throw new AppError('Ronda no encontrada', ErrorCode.NOT_FOUND)
      }

      if (round.status !== 'IN_PROGRESS') {
        throw new AppError('La ronda no está en progreso', ErrorCode.BAD_REQUEST)
      }

      // Validar que el número no ha sido sacado
      const existingDraw = round.draws.find(draw => draw.number === request.number)
      if (existingDraw) {
        throw new AppError('Este número ya fue sacado', ErrorCode.BAD_REQUEST)
      }

      // Validar rango del número y determinar letra
      const { letter, isValid } = this.getNumberLetter(request.number)
      if (!isValid) {
        throw new AppError('Número fuera del rango válido (1-75)', ErrorCode.BAD_REQUEST)
      }

      const draw = await prisma.bingoDraw.create({
        data: {
          roomId: request.roomId,
          roundId: request.roundId,
          number: request.number,
          letter,
          drawnAt: new Date()
        }
      })

      // Actualizar último número sacado en la ronda
      await prisma.bingoRound.update({
        where: { id: request.roundId },
        data: { lastChip: request.number }
      })

      logger.info(`Número sacado: ${letter}${request.number}`, {
        roundId: request.roundId,
        roomId: request.roomId
      })

      return {
        id: draw.id,
        roomId: draw.roomId,
        roundId: draw.roundId,
        number: draw.number,
        letter: draw.letter as 'B' | 'I' | 'N' | 'G' | 'O',
        drawnAt: draw.drawnAt
      }
    } catch (error) {
      logger.error('Error sacando número:', error)
      throw error instanceof AppError ? error : new AppError('Error sacando número')
    }
  }

  /**
   * Procesar claim de BINGO
   */
  async processBingoClaim(roundId: string, userId: string, cardId: string, claimedPattern?: string): Promise<{ isValid: boolean, winner?: BingoWinnerResponse }> {
    try {
      const round = await prisma.bingoRound.findUnique({
        where: { id: roundId },
        include: {
          draws: true,
          room: true
        }
      })

      if (!round) {
        throw new AppError('Ronda no encontrada', ErrorCode.NOT_FOUND)
      }

      if (round.status !== 'IN_PROGRESS') {
        throw new AppError('La ronda no está activa', ErrorCode.BAD_REQUEST)
      }

      // Obtener números sacados
      const drawnNumbers = round.draws.map(draw => draw.number)

      // Validar BINGO usando el servicio de cartones
      const { BingoCardService } = await import('./BingoCardService')
      const cardService = new BingoCardService()
      
      const validation = await cardService.validateBingo(cardId, drawnNumbers, claimedPattern)

      if (!validation.isValid) {
        logger.warn(`Claim de BINGO inválido`, { userId, cardId, roundId })
        return { isValid: false }
      }

      // Calcular premio
      const prizeAmount = this.calculatePrizeAmount(round, validation.prizeType as BingoPrizeType)

      // Crear ganador
      const winner = await prisma.bingoRoomUserWinner.create({
        data: {
          roundId,
          userId,
          cardId,
          prizeType: validation.prizeType as BingoPrizeType,
          prizeAmount,
          winTime: new Date(),
          pattern: validation.pattern
        },
        include: {
          user: {
            select: { id: true, username: true }
          },
          card: {
            select: { id: true, serialNumber: true }
          }
        }
      })

      // Acreditar premio a la billetera del usuario
      await this.creditPrize(userId, prizeAmount, round.roomId)

      // Si es FULL HOUSE, finalizar la ronda
      if (validation.prizeType === 'FULL_HOUSE') {
        await this.endRound(roundId)
      }

      const winnerResponse: BingoWinnerResponse = {
        id: winner.id,
        userId: winner.userId,
        username: winner.user.username,
        cardId: winner.cardId,
        cardSerial: winner.card.serialNumber,
        prizeType: winner.prizeType as BingoPrizeType,
        prizeAmount: winner.prizeAmount,
        winTime: winner.winTime,
        pattern: winner.pattern || undefined
      }

      logger.info(`BINGO válido procesado`, {
        userId,
        cardId,
        roundId,
        prizeType: validation.prizeType,
        prizeAmount
      })

      return { isValid: true, winner: winnerResponse }
    } catch (error) {
      logger.error('Error procesando claim de BINGO:', error)
      throw error instanceof AppError ? error : new AppError('Error procesando BINGO')
    }
  }

  /**
   * Finalizar ronda
   */
  async endRound(roundId: string): Promise<BingoRoundResponse> {
    try {
      const round = await prisma.bingoRound.update({
        where: { id: roundId },
        data: {
          status: 'FINISHED' as BingoRoundStatus
        },
        include: {
          room: true,
          pattern: true,
          draws: {
            orderBy: { drawnAt: 'asc' }
          },
          winners: {
            include: {
              user: {
                select: { id: true, username: true }
              },
              card: {
                select: { id: true, serialNumber: true }
              }
            }
          }
        }
      })

      logger.info(`Ronda finalizada: ${roundId}`)

      return this.formatRoundResponse(round)
    } catch (error) {
      logger.error('Error finalizando ronda:', error)
      throw error instanceof AppError ? error : new AppError('Error finalizando ronda')
    }
  }

  /**
   * Obtener ronda actual de una sala
   */
  async getCurrentRound(roomId: string): Promise<BingoRoundResponse | null> {
    try {
      const round = await prisma.bingoRound.findFirst({
        where: {
          roomId,
          status: { in: ['WAITING', 'IN_PROGRESS'] }
        },
        include: {
          room: true,
          pattern: true,
          draws: {
            orderBy: { drawnAt: 'asc' }
          },
          winners: {
            include: {
              user: {
                select: { id: true, username: true }
              },
              card: {
                select: { id: true, serialNumber: true }
              }
            }
          }
        },
        orderBy: { createdAt: 'desc' }
      })

      return round ? this.formatRoundResponse(round) : null
    } catch (error) {
      logger.error('Error obteniendo ronda actual:', error)
      throw new AppError('Error obteniendo ronda actual')
    }
  }

  /**
   * Obtener historial de rondas de una sala
   */
  async getRoundsHistory(roomId: string, limit = 10): Promise<BingoRoundResponse[]> {
    try {
      const rounds = await prisma.bingoRound.findMany({
        where: { roomId },
        include: {
          room: true,
          pattern: true,
          draws: {
            orderBy: { drawnAt: 'asc' }
          },
          winners: {
            include: {
              user: {
                select: { id: true, username: true }
              },
              card: {
                select: { id: true, serialNumber: true }
              }
            }
          }
        },
        orderBy: { createdAt: 'desc' },
        take: limit
      })

      return rounds.map(round => this.formatRoundResponse(round))
    } catch (error) {
      logger.error('Error obteniendo historial de rondas:', error)
      throw new AppError('Error obteniendo historial de rondas')
    }
  }

  /**
   * Formatear respuesta de ronda
   */
  private formatRoundResponse(round: any): BingoRoundResponse {
    return {
      id: round.id,
      roomId: round.roomId,
      round: round.round,
      startTime: round.startTime,
      lastChip: round.lastChip,
      bingoPatternId: round.bingoPatternId,
      prizeType: round.prizeType as BingoPrizeType,
      prizeValue: round.prizeValue,
      status: round.status as BingoRoundStatus,
      drawnNumbers: round.draws?.map((draw: any) => draw.number) || [],
      winners: round.winners?.map((winner: any) => ({
        id: winner.id,
        userId: winner.userId,
        username: winner.user.username,
        cardId: winner.cardId,
        cardSerial: winner.card.serialNumber,
        prizeType: winner.prizeType as BingoPrizeType,
        prizeAmount: winner.prizeAmount,
        winTime: winner.winTime,
        pattern: winner.pattern
      })) || [],
      createdAt: round.createdAt,
      updatedAt: round.updatedAt
    }
  }

  /**
   * Obtener letra correspondiente a un número
   */
  private getNumberLetter(number: number): { letter: string, isValid: boolean } {
    if (number < 1 || number > 75) {
      return { letter: '', isValid: false }
    }

    if (number <= 15) return { letter: 'B', isValid: true }
    if (number <= 30) return { letter: 'I', isValid: true }
    if (number <= 45) return { letter: 'N', isValid: true }
    if (number <= 60) return { letter: 'G', isValid: true }
    return { letter: 'O', isValid: true }
  }

  /**
   * Calcular cantidad del premio
   */
  private calculatePrizeAmount(round: any, prizeType: BingoPrizeType): number {
    switch (prizeType) {
      case 'FULL_HOUSE':
        return round.room.fullHousePrize || round.room.prizePool * 0.6
      case 'SERIAL':
        return round.room.serialPrize || round.room.prizePool * 0.3
      case 'PATTERN':
        return round.prizeValue || round.room.prizePool * 0.1
      default:
        return 0
    }
  }

  /**
   * Acreditar premio a la billetera del usuario
   */
  private async creditPrize(userId: string, amount: number, roomId: string): Promise<void> {
    try {
      // Buscar billetera principal del usuario
      const wallet = await prisma.wallet.findFirst({
        where: { 
          userId,
          isPrimary: true
        }
      })

      if (wallet) {
        await prisma.$transaction(async (tx) => {
          // Acreditar a la billetera
          await tx.wallet.update({
            where: { id: wallet.id },
            data: { 
              balance: { increment: amount },
              updatedAt: new Date()
            }
          })

          // Crear transacción
          await tx.transaction.create({
            data: {
              userId,
              type: 'CREDIT',
              amount,
              currencyId: wallet.currencyId,
              description: `Premio BINGO - Sala ${roomId}`,
              status: 'COMPLETED',
              reference: `BINGO_PRIZE_${roomId}`,
              metadata: {
                roomId,
                source: 'BINGO_PRIZE'
              }
            }
          })
        })
      }
    } catch (error) {
      logger.error('Error acreditando premio:', error)
      throw new AppError('Error procesando premio')
    }
  }
}