import { prisma } from '../../../utils/database'
import { logger } from '../../../utils/logger'
import { AppError, ErrorCode } from '../../../types/errors'
import type { 
  BingoCardResponse, 
  BingoCardMatrix, 
  BuyCardsRequest,
  BingoCardLotResponse 
} from '../../../types/games/bingo'

export class BingoCardService {

  /**
   * Generar y comprar cartones para un usuario en una sala
   */
  async buyCards(request: BuyCardsRequest, userId: string): Promise<{ cards: BingoCardResponse[], transaction: any }> {
    try {
      // Validar sala existe y está disponible
      const room = await prisma.bingoRoom.findUnique({
        where: { id: request.roomId },
        include: {
          _count: { select: { cards: true } }
        }
      })

      if (!room) {
        throw new AppError('Sala no encontrada', ErrorCode.NOT_FOUND)
      }

      if (room.status !== 1) {
        throw new AppError('La sala no está disponible para comprar cartones', ErrorCode.BAD_REQUEST)
      }

      // Validar límites de cartones
      if (room._count.cards + request.quantity > room.ctns) {
        throw new AppError('No hay suficientes cartones disponibles', ErrorCode.BAD_REQUEST)
      }

      // Validar usuario tiene fondos suficientes
      const totalCost = request.quantity * room.cardPrice
      const userWallet = await prisma.wallet.findFirst({
        where: { 
          userId,
          currencyId: request.currencyId
        }
      })

      if (!userWallet || userWallet.balance < totalCost) {
        throw new AppError('Fondos insuficientes', ErrorCode.BAD_REQUEST)
      }

      // Verificar si el usuario ya está en la sala
      let roomUser = await prisma.bingoRoomUser.findUnique({
        where: {
          roomId_userId: {
            roomId: request.roomId,
            userId
          }
        }
      })

      // Si no está en la sala, agregarlo
      if (!roomUser) {
        roomUser = await prisma.bingoRoomUser.create({
          data: {
            roomId: request.roomId,
            userId,
            joinedAt: new Date()
          }
        })
      }

      // Crear lote de cartones
      const lot = await this.createCardLot(request.roomId, userId, request.quantity)

      // Generar cartones
      const cards: BingoCardResponse[] = []
      const cardPromises = []

      for (let i = 0; i < request.quantity; i++) {
        const matrix = this.generateBingoMatrix()
        const serialNumber = await this.generateUniqueSerial()

        cardPromises.push(
          prisma.bingoCard.create({
            data: {
              roomId: request.roomId,
              userId,
              roomUserId: roomUser.id,
              cardLotId: lot.id,
              currencyId: request.currencyId,
              serialNumber,
              price: room.cardPrice,
              isActive: true,
              purchaseDate: new Date(),
              // Matriz BINGO
              b1: matrix.B[0], b2: matrix.B[1], b3: matrix.B[2], b4: matrix.B[3], b5: matrix.B[4],
              i1: matrix.I[0], i2: matrix.I[1], i3: matrix.I[2], i4: matrix.I[3], i5: matrix.I[4],
              n1: matrix.N[0], n2: matrix.N[1], n3: matrix.N[2], n4: matrix.N[3], n5: matrix.N[4],
              g1: matrix.G[0], g2: matrix.G[1], g3: matrix.G[2], g4: matrix.G[3], g5: matrix.G[4],
              o1: matrix.O[0], o2: matrix.O[1], o3: matrix.O[2], o4: matrix.O[3], o5: matrix.O[4],
            },
            include: {
              lot: true,
              currency: true
            }
          })
        )
      }

      const createdCards = await Promise.all(cardPromises)

      // Procesar pago
      const transaction = await prisma.$transaction(async (tx) => {
        // Debitar de la billetera del usuario
        await tx.wallet.update({
          where: { id: userWallet.id },
          data: { 
            balance: { decrement: totalCost },
            updatedAt: new Date()
          }
        })

        // Crear transacción
        const transaction = await tx.transaction.create({
          data: {
            userId,
            type: 'DEBIT',
            amount: totalCost,
            currencyId: request.currencyId,
            description: `Compra de ${request.quantity} cartones en sala ${room.encrypted}`,
            status: 'COMPLETED',
            reference: `BINGO_CARDS_${lot.id}`,
            metadata: {
              roomId: request.roomId,
              lotId: lot.id,
              quantity: request.quantity
            }
          }
        })

        // Actualizar pool de premios de la sala
        const commission = totalCost * (room.rkeCommn / 100)
        const prizeContribution = totalCost - commission

        await tx.bingoRoom.update({
          where: { id: request.roomId },
          data: {
            prizePool: { increment: prizeContribution }
          }
        })

        return transaction
      })

      // Formatear respuesta de cartones
      for (const card of createdCards) {
        cards.push(this.formatCardResponse(card))
      }

      logger.info(`Usuario compró ${request.quantity} cartones`, {
        userId,
        roomId: request.roomId,
        lotId: lot.id,
        totalCost
      })

      return { cards, transaction }

    } catch (error) {
      logger.error('Error comprando cartones:', error)
      throw error instanceof AppError ? error : new AppError('Error procesando compra de cartones')
    }
  }

  /**
   * Obtener cartones de un usuario en una sala
   */
  async getUserCards(roomId: string, userId: string): Promise<BingoCardResponse[]> {
    try {
      const cards = await prisma.bingoCard.findMany({
        where: {
          roomId,
          userId,
          isActive: true
        },
        include: {
          lot: true,
          currency: true
        },
        orderBy: { serialNumber: 'asc' }
      })

      return cards.map(card => this.formatCardResponse(card))
    } catch (error) {
      logger.error('Error obteniendo cartones del usuario:', error)
      throw new AppError('Error obteniendo cartones')
    }
  }

  /**
   * Obtener cartón por ID
   */
  async getCardById(cardId: string): Promise<BingoCardResponse> {
    try {
      const card = await prisma.bingoCard.findUnique({
        where: { id: cardId },
        include: {
          lot: true,
          currency: true,
          user: {
            select: { id: true, username: true }
          }
        }
      })

      if (!card) {
        throw new AppError('Cartón no encontrado', ErrorCode.NOT_FOUND)
      }

      return this.formatCardResponse(card)
    } catch (error) {
      logger.error('Error obteniendo cartón:', error)
      throw error instanceof AppError ? error : new AppError('Error obteniendo cartón')
    }
  }

  /**
   * Validar si un cartón tiene BINGO
   */
  async validateBingo(cardId: string, drawnNumbers: number[], claimedPattern?: string): Promise<{ isValid: boolean, prizeType: string, pattern?: string }> {
    try {
      const card = await this.getCardById(cardId)
      const matrix = card.cardMatrix

      // Convertir matriz a array de números para facilitar validación
      const cardNumbers = this.matrixToNumbers(matrix)

      // Validar FULL HOUSE (cartón completo)
      const fullHouseValid = this.validateFullHouse(cardNumbers, drawnNumbers)
      if (fullHouseValid) {
        return { isValid: true, prizeType: 'FULL_HOUSE' }
      }

      // Validar SERIAL (línea)
      const serialValidation = this.validateSerial(matrix, drawnNumbers)
      if (serialValidation.isValid) {
        return { isValid: true, prizeType: 'SERIAL', pattern: serialValidation.pattern }
      }

      // Validar patrón específico si se proporciona
      if (claimedPattern) {
        const patternValid = this.validatePattern(matrix, drawnNumbers, claimedPattern)
        if (patternValid) {
          return { isValid: true, prizeType: 'PATTERN', pattern: claimedPattern }
        }
      }

      return { isValid: false, prizeType: '' }
    } catch (error) {
      logger.error('Error validando BINGO:', error)
      throw new AppError('Error validando BINGO')
    }
  }

  /**
   * Crear lote de cartones
   */
  private async createCardLot(roomId: string, userId: string, quantity: number): Promise<any> {
    const serialLot = `LOT-${Date.now()}-${Math.random().toString(36).substring(2, 8)}`
    
    return await prisma.bingoCardLot.create({
      data: {
        roomId,
        userId,
        serial: serialLot,
        quantity,
        createdAt: new Date()
      }
    })
  }

  /**
   * Generar matriz de BINGO
   */
  private generateBingoMatrix(): BingoCardMatrix {
    const matrix: BingoCardMatrix = {
      B: [0, 0, 0, 0, 0],
      I: [0, 0, 0, 0, 0],
      N: [0, 0, 0, 0, 0],
      G: [0, 0, 0, 0, 0],
      O: [0, 0, 0, 0, 0]
    }

    // B: 1-15
    matrix.B = this.generateUniqueNumbers(1, 15, 5) as [number, number, number, number, number]
    
    // I: 16-30
    matrix.I = this.generateUniqueNumbers(16, 30, 5) as [number, number, number, number, number]
    
    // N: 31-45 (centro libre = 0)
    const nNumbers = this.generateUniqueNumbers(31, 45, 5)
    matrix.N = nNumbers as [number, number, number, number, number]
    matrix.N[2] = 0 // Centro libre
    
    // G: 46-60
    matrix.G = this.generateUniqueNumbers(46, 60, 5) as [number, number, number, number, number]
    
    // O: 61-75
    matrix.O = this.generateUniqueNumbers(61, 75, 5) as [number, number, number, number, number]

    return matrix
  }

  /**
   * Generar números únicos en un rango
   */
  private generateUniqueNumbers(min: number, max: number, count: number): number[] {
    const numbers: number[] = []
    const available = Array.from({ length: max - min + 1 }, (_, i) => min + i)
    
    for (let i = 0; i < count; i++) {
      const randomIndex = Math.floor(Math.random() * available.length)
      numbers.push(available.splice(randomIndex, 1)[0])
    }
    
    return numbers.sort((a, b) => a - b)
  }

  /**
   * Generar número de serie único
   */
  private async generateUniqueSerial(): Promise<string> {
    let serial: string
    let exists: boolean

    do {
      serial = 'BG' + Date.now().toString().slice(-8) + Math.random().toString(36).substring(2, 6).toUpperCase()
      const existing = await prisma.bingoCard.findUnique({
        where: { serialNumber: serial }
      })
      exists = !!existing
    } while (exists)

    return serial
  }

  /**
   * Formatear respuesta de cartón
   */
  private formatCardResponse(card: any): BingoCardResponse {
    return {
      id: card.id,
      roomId: card.roomId,
      userId: card.userId,
      serialNumber: card.serialNumber,
      cardMatrix: {
        B: [card.b1, card.b2, card.b3, card.b4, card.b5],
        I: [card.i1, card.i2, card.i3, card.i4, card.i5],
        N: [card.n1, card.n2, card.n3, card.n4, card.n5],
        G: [card.g1, card.g2, card.g3, card.g4, card.g5],
        O: [card.o1, card.o2, card.o3, card.o4, card.o5]
      },
      isActive: card.isActive,
      purchaseDate: card.purchaseDate,
      price: card.price,
      currencyId: card.currencyId,
      lot: {
        id: card.lot.id,
        serial: card.lot.serial
      }
    }
  }

  /**
   * Convertir matriz a array de números
   */
  private matrixToNumbers(matrix: BingoCardMatrix): number[] {
    return [
      ...matrix.B,
      ...matrix.I,
      ...matrix.N,
      ...matrix.G,
      ...matrix.O
    ].filter(num => num > 0) // Excluir el centro libre (0)
  }

  /**
   * Validar FULL HOUSE
   */
  private validateFullHouse(cardNumbers: number[], drawnNumbers: number[]): boolean {
    return cardNumbers.every(num => drawnNumbers.includes(num))
  }

  /**
   * Validar SERIAL (líneas)
   */
  private validateSerial(matrix: BingoCardMatrix, drawnNumbers: number[]): { isValid: boolean, pattern?: string } {
    // Validar líneas horizontales
    const rows = [
      matrix.B.concat(matrix.I[0], matrix.N[0], matrix.G[0], matrix.O[0]),
      [matrix.B[1], matrix.I[1], matrix.N[1], matrix.G[1], matrix.O[1]],
      [matrix.B[2], matrix.I[2], matrix.N[2], matrix.G[2], matrix.O[2]],
      [matrix.B[3], matrix.I[3], matrix.N[3], matrix.G[3], matrix.O[3]],
      [matrix.B[4], matrix.I[4], matrix.N[4], matrix.G[4], matrix.O[4]]
    ]

    for (let i = 0; i < rows.length; i++) {
      const row = rows[i].filter(num => num > 0 || i === 2) // Centro libre en fila 3
      if (row.every(num => num === 0 || drawnNumbers.includes(num))) {
        return { isValid: true, pattern: `ROW_${i + 1}` }
      }
    }

    // Validar columnas
    const columns = [matrix.B, matrix.I, matrix.N, matrix.G, matrix.O]
    const columnNames = ['B', 'I', 'N', 'G', 'O']

    for (let i = 0; i < columns.length; i++) {
      const column = columns[i].filter((num, idx) => num > 0 || (i === 2 && idx === 2))
      if (column.every(num => num === 0 || drawnNumbers.includes(num))) {
        return { isValid: true, pattern: `COLUMN_${columnNames[i]}` }
      }
    }

    return { isValid: false }
  }

  /**
   * Validar patrón específico
   */
  private validatePattern(matrix: BingoCardMatrix, drawnNumbers: number[], pattern: string): boolean {
    // Implementar lógica de patrones específicos
    // Por ahora retorna false, se puede expandir según patrones requeridos
    return false
  }
}

export interface BingoCardLotResponse {
  id: string
  roomId: string
  userId: string
  serial: string
  quantity: number
  createdAt: Date
}