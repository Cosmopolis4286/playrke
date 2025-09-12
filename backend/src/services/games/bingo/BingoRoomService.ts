import { prisma } from '../../../utils/database'
import { logger } from '../../../utils/logger'
import { AppError, ErrorCode } from '../../../types/errors'
import type { 
  CreateBingoRoomRequest, 
  BingoRoomResponse, 
  BingoRoomListResponse,
  UpdateBingoRoomRequest 
} from '../../../types/games/bingo'

export class BingoRoomService {
  
  /**
   * Obtener sala de bingo por ID
   */
  async getRoomById(roomId: string, userId?: string): Promise<BingoRoomResponse> {
    try {
      const room = await prisma.bingoRoom.findUnique({
        where: { id: roomId },
        include: {
          creator: {
            select: { id: true, username: true, email: true }
          },
          status: true,
          config: true,
          players: userId ? {
            where: { userId }
          } : false,
          _count: {
            select: { players: true, cards: true }
          }
        }
      })

      if (!room) {
        throw new AppError('Sala no encontrada', ErrorCode.NOT_FOUND)
      }

      return this.formatRoomResponse(room)
    } catch (error) {
      logger.error('Error obteniendo sala de bingo:', error)
      throw error instanceof AppError ? error : new AppError('Error interno del servidor')
    }
  }

  /**
   * Obtener sala por código encriptado
   */
  async getRoomByEncrypted(encrypted: string): Promise<BingoRoomResponse> {
    try {
      const room = await prisma.bingoRoom.findUnique({
        where: { encrypted },
        include: {
          creator: {
            select: { id: true, username: true, email: true }
          },
          status: true,
          config: true,
          _count: {
            select: { players: true, cards: true }
          }
        }
      })

      if (!room) {
        throw new AppError('Sala no encontrada', ErrorCode.NOT_FOUND)
      }

      return this.formatRoomResponse(room)
    } catch (error) {
      logger.error('Error obteniendo sala por código:', error)
      throw error instanceof AppError ? error : new AppError('Error interno del servidor')
    }
  }

  /**
   * Crear nueva sala de bingo
   */
  async createRoom(data: CreateBingoRoomRequest, creatorId: string): Promise<BingoRoomResponse> {
    try {
      // Validar que el usuario puede crear salas
      const user = await prisma.user.findUnique({
        where: { id: creatorId },
        include: { roles: true }
      })

      if (!user) {
        throw new AppError('Usuario no encontrado', ErrorCode.NOT_FOUND)
      }

      // Generar código único encriptado
      const encrypted = await this.generateUniqueEncryptedCode()
      const uuidCode = await this.generateUniqueUUID()

      const room = await prisma.bingoRoom.create({
        data: {
          encrypted,
          uuidCode,
          status: 1, // OPEN
          roomType: data.roomType,
          creatorUserId: creatorId,
          paidWin: data.paidWin || false,
          publicationDate: data.publicationDate || new Date(),
          cardPrice: data.cardPrice,
          players: data.maxPlayers || 50,
          ctns: data.maxCards || 100,
          allSale: data.allSale || false,
          rkeCommn: data.rkeCommission || 0,
          roomCommn: data.roomCommission || 0,
          sellerCommn: data.sellerCommission || 0,
          prizePool: 0, // Se calcula después
          fullHousePrize: data.fullHousePrize || 0,
          serialPrize: data.serialPrize || 0,
          closeRoom: false
        },
        include: {
          creator: {
            select: { id: true, username: true, email: true }
          },
          status: true
        }
      })

      logger.info(`Sala de bingo creada: ${room.id}`, { creatorId, roomType: data.roomType })
      
      return this.formatRoomResponse(room)
    } catch (error) {
      logger.error('Error creando sala de bingo:', error)
      throw error instanceof AppError ? error : new AppError('Error creando sala de bingo')
    }
  }

  /**
   * Actualizar sala de bingo
   */
  async updateRoom(roomId: string, data: UpdateBingoRoomRequest, userId: string): Promise<BingoRoomResponse> {
    try {
      // Verificar que el usuario es el creador
      const existingRoom = await prisma.bingoRoom.findUnique({
        where: { id: roomId }
      })

      if (!existingRoom) {
        throw new AppError('Sala no encontrada', ErrorCode.NOT_FOUND)
      }

      if (existingRoom.creatorUserId !== userId) {
        throw new AppError('No tienes permisos para modificar esta sala', ErrorCode.FORBIDDEN)
      }

      const room = await prisma.bingoRoom.update({
        where: { id: roomId },
        data: {
          ...data,
          updatedAt: new Date()
        },
        include: {
          creator: {
            select: { id: true, username: true, email: true }
          },
          status: true,
          config: true
        }
      })

      logger.info(`Sala de bingo actualizada: ${roomId}`, { userId })
      
      return this.formatRoomResponse(room)
    } catch (error) {
      logger.error('Error actualizando sala de bingo:', error)
      throw error instanceof AppError ? error : new AppError('Error actualizando sala')
    }
  }

  /**
   * Listar salas disponibles
   */
  async listAvailableRooms(userId?: string, filters?: any): Promise<BingoRoomListResponse> {
    try {
      const where: any = {
        status: 1, // OPEN
        closeRoom: false,
        publicationDate: {
          lte: new Date()
        }
      }

      // Aplicar filtros
      if (filters?.roomType) {
        where.roomType = filters.roomType
      }

      if (filters?.creatorId) {
        where.creatorUserId = filters.creatorId
      }

      const [rooms, total] = await Promise.all([
        prisma.bingoRoom.findMany({
          where,
          include: {
            creator: {
              select: { id: true, username: true }
            },
            status: true,
            _count: {
              select: { players: true, cards: true }
            }
          },
          orderBy: { publicationDate: 'desc' },
          take: filters?.limit || 20,
          skip: filters?.offset || 0
        }),
        prisma.bingoRoom.count({ where })
      ])

      return {
        rooms: rooms.map(room => this.formatRoomResponse(room)),
        total,
        page: Math.floor((filters?.offset || 0) / (filters?.limit || 20)) + 1,
        totalPages: Math.ceil(total / (filters?.limit || 20))
      }
    } catch (error) {
      logger.error('Error listando salas:', error)
      throw new AppError('Error obteniendo salas disponibles')
    }
  }

  /**
   * Unirse a una sala
   */
  async joinRoom(roomId: string, userId: string): Promise<{ success: boolean, message: string }> {
    try {
      const room = await prisma.bingoRoom.findUnique({
        where: { id: roomId },
        include: {
          _count: { select: { players: true } }
        }
      })

      if (!room) {
        throw new AppError('Sala no encontrada', ErrorCode.NOT_FOUND)
      }

      if (room.status !== 1) {
        throw new AppError('La sala no está disponible', ErrorCode.BAD_REQUEST)
      }

      if (room._count.players >= room.players) {
        throw new AppError('La sala está llena', ErrorCode.BAD_REQUEST)
      }

      // Verificar si ya está en la sala
      const existingPlayer = await prisma.bingoRoomUser.findUnique({
        where: {
          roomId_userId: {
            roomId,
            userId
          }
        }
      })

      if (existingPlayer) {
        return { success: true, message: 'Ya estás en esta sala' }
      }

      await prisma.bingoRoomUser.create({
        data: {
          roomId,
          userId,
          joinedAt: new Date()
        }
      })

      logger.info(`Usuario se unió a sala: ${roomId}`, { userId })
      
      return { success: true, message: 'Te has unido a la sala exitosamente' }
    } catch (error) {
      logger.error('Error uniéndose a sala:', error)
      throw error instanceof AppError ? error : new AppError('Error uniéndose a la sala')
    }
  }

  /**
   * Formatear respuesta de sala
   */
  private formatRoomResponse(room: any): BingoRoomResponse {
    return {
      id: room.id,
      encrypted: room.encrypted,
      uuidCode: room.uuidCode,
      status: room.status,
      roomType: room.roomType,
      roomTypeDescription: this.getRoomTypeDescription(room.roomType),
      creator: room.creator,
      paidWin: room.paidWin,
      publicationDate: room.publicationDate,
      cardPrice: room.cardPrice,
      players: room.players,
      ctns: room.ctns,
      allSale: room.allSale,
      rkeCommn: room.rkeCommn,
      roomCommn: room.roomCommn,
      sellerCommn: room.sellerCommn,
      prizePool: room.prizePool,
      fullHousePrize: room.fullHousePrize,
      serialPrize: room.serialPrize,
      closeRoom: room.closeRoom,
      currentPlayers: room._count?.players || 0,
      currentCards: room._count?.cards || 0,
      config: room.config,
      createdAt: room.createdAt,
      updatedAt: room.updatedAt
    }
  }

  /**
   * Obtener descripción del tipo de sala
   */
  private getRoomTypeDescription(roomType: number): string {
    const types: { [key: number]: string } = {
      1: 'Sala Normal',
      2: 'Sala VIP',
      3: 'Sala Premium',
      4: 'Torneo'
    }
    return types[roomType] || 'Desconocido'
  }

  /**
   * Generar código encriptado único
   */
  private async generateUniqueEncryptedCode(): Promise<string> {
    let encrypted: string
    let exists: boolean

    do {
      encrypted = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15)
      const existing = await prisma.bingoRoom.findUnique({
        where: { encrypted }
      })
      exists = !!existing
    } while (exists)

    return encrypted
  }

  /**
   * Generar UUID único
   */
  private async generateUniqueUUID(): Promise<string> {
    let uuid: string
    let exists: boolean

    do {
      uuid = 'room-' + Date.now() + '-' + Math.random().toString(36).substring(2, 15)
      const existing = await prisma.bingoRoom.findUnique({
        where: { uuidCode: uuid }
      })
      exists = !!existing
    } while (exists)

    return uuid
  }
}