import { Server } from 'socket.io'
import { DefaultEventsMap } from 'socket.io/dist/typed-events'
import { logger } from '../utils/logger'
import { prisma } from '../utils/database'
import { BingoWebSocketEvents } from '../types/games/bingo'

export class BingoWebSocketHandler {
  private io: Server<DefaultEventsMap, DefaultEventsMap>
  private connectedUsers: Map<string, { userId: string, username: string, rooms: Set<string> }> = new Map()

  constructor(io: Server<DefaultEventsMap, DefaultEventsMap>) {
    this.io = io
    this.setupBingoNamespace()
  }

  private setupBingoNamespace() {
    const bingoNamespace = this.io.of('/bingo')

    bingoNamespace.on('connection', (socket) => {
      logger.info(`Cliente conectado a Bingo WebSocket: ${socket.id}`)

      // Autenticar usuario
      socket.on('authenticate', async (data: { token: string }) => {
        try {
          // Aquí deberías validar el token JWT
          // Por ahora simulamos la validación
          const userId = this.validateToken(data.token)
          if (!userId) {
            socket.emit('auth_error', { message: 'Token inválido' })
            socket.disconnect()
            return
          }

          // Obtener información del usuario
          const user = await prisma.user.findUnique({
            where: { id: userId },
            select: { id: true, username: true, email: true }
          })

          if (!user) {
            socket.emit('auth_error', { message: 'Usuario no encontrado' })
            socket.disconnect()
            return
          }

          // Registrar usuario conectado
          this.connectedUsers.set(socket.id, {
            userId: user.id,
            username: user.username,
            rooms: new Set()
          })

          socket.emit('authenticated', { 
            userId: user.id, 
            username: user.username 
          })

          logger.info(`Usuario autenticado en Bingo WebSocket: ${user.username} (${socket.id})`)

        } catch (error) {
          logger.error('Error autenticando usuario en WebSocket:', error)
          socket.emit('auth_error', { message: 'Error de autenticación' })
          socket.disconnect()
        }
      })

      // Unirse a sala de bingo
      socket.on('join_room', async (data: { roomId: string }) => {
        const userInfo = this.connectedUsers.get(socket.id)
        if (!userInfo) {
          socket.emit('error', { message: 'Usuario no autenticado' })
          return
        }

        try {
          // Verificar que la sala existe
          const room = await prisma.bingoRoom.findUnique({
            where: { id: data.roomId },
            select: { id: true, encrypted: true, status: true }
          })

          if (!room) {
            socket.emit('room_error', { message: 'Sala no encontrada' })
            return
          }

          // Unirse a la sala de Socket.IO
          await socket.join(`bingo_room_${data.roomId}`)
          userInfo.rooms.add(data.roomId)

          // Notificar a otros usuarios de la sala
          socket.to(`bingo_room_${data.roomId}`).emit('user_joined', {
            userId: userInfo.userId,
            username: userInfo.username,
            roomId: data.roomId
          })

          socket.emit('room_joined', { 
            roomId: data.roomId,
            message: 'Te has unido a la sala exitosamente'
          })

          logger.info(`Usuario ${userInfo.username} se unió a sala ${data.roomId}`)

        } catch (error) {
          logger.error('Error uniéndose a sala WebSocket:', error)
          socket.emit('room_error', { message: 'Error uniéndose a la sala' })
        }
      })

      // Salir de sala de bingo
      socket.on('leave_room', async (data: { roomId: string }) => {
        const userInfo = this.connectedUsers.get(socket.id)
        if (!userInfo) return

        await socket.leave(`bingo_room_${data.roomId}`)
        userInfo.rooms.delete(data.roomId)

        // Notificar a otros usuarios
        socket.to(`bingo_room_${data.roomId}`).emit('user_left', {
          userId: userInfo.userId,
          username: userInfo.username,
          roomId: data.roomId
        })

        socket.emit('room_left', { roomId: data.roomId })
        logger.info(`Usuario ${userInfo.username} salió de sala ${data.roomId}`)
      })

      // Reclamar BINGO
      socket.on('claim_bingo', async (data: { roundId: string, cardId: string, pattern?: string }) => {
        const userInfo = this.connectedUsers.get(socket.id)
        if (!userInfo) {
          socket.emit('error', { message: 'Usuario no autenticado' })
          return
        }

        try {
          // Aquí se integraría con el BingoRoundService
          const { BingoRoundService } = await import('../services/games/bingo/BingoRoundService')
          const roundService = new BingoRoundService()

          const result = await roundService.processBingoClaim(
            data.roundId,
            userInfo.userId,
            data.cardId,
            data.pattern
          )

          if (result.isValid && result.winner) {
            // Notificar a toda la sala sobre el BINGO
            const round = await prisma.bingoRound.findUnique({
              where: { id: data.roundId },
              select: { roomId: true }
            })

            if (round) {
              this.io.of('/bingo').to(`bingo_room_${round.roomId}`).emit('bingo_winner', {
                roundId: data.roundId,
                roomId: round.roomId,
                winner: result.winner
              })
            }

            socket.emit('bingo_claimed', {
              success: true,
              winner: result.winner,
              message: '¡BINGO válido!'
            })
          } else {
            socket.emit('bingo_claimed', {
              success: false,
              message: 'BINGO no válido'
            })
          }

        } catch (error) {
          logger.error('Error procesando claim de BINGO:', error)
          socket.emit('bingo_error', { message: 'Error procesando BINGO' })
        }
      })

      // Obtener estado de la sala
      socket.on('get_room_state', async (data: { roomId: string }) => {
        try {
          const room = await prisma.bingoRoom.findUnique({
            where: { id: data.roomId },
            include: {
              _count: { select: { players: true, cards: true } },
              rounds: {
                where: { status: { in: ['WAITING', 'IN_PROGRESS'] } },
                include: {
                  draws: { orderBy: { drawnAt: 'asc' } }
                },
                take: 1,
                orderBy: { createdAt: 'desc' }
              }
            }
          })

          if (room) {
            socket.emit('room_state', {
              room: {
                id: room.id,
                status: room.status,
                currentPlayers: room._count.players,
                currentCards: room._count.cards,
                prizePool: room.prizePool
              },
              currentRound: room.rounds[0] ? {
                id: room.rounds[0].id,
                round: room.rounds[0].round,
                status: room.rounds[0].status,
                drawnNumbers: room.rounds[0].draws.map(draw => draw.number),
                startTime: room.rounds[0].startTime
              } : null
            })
          }

        } catch (error) {
          logger.error('Error obteniendo estado de sala:', error)
          socket.emit('room_error', { message: 'Error obteniendo estado de sala' })
        }
      })

      // Desconexión
      socket.on('disconnect', () => {
        const userInfo = this.connectedUsers.get(socket.id)
        if (userInfo) {
          // Notificar a todas las salas donde estaba el usuario
          userInfo.rooms.forEach(roomId => {
            socket.to(`bingo_room_${roomId}`).emit('user_left', {
              userId: userInfo.userId,
              username: userInfo.username,
              roomId
            })
          })

          this.connectedUsers.delete(socket.id)
          logger.info(`Usuario ${userInfo.username} desconectado de Bingo WebSocket`)
        }
      })
    })
  }

  // Métodos públicos para emitir eventos desde los servicios

  /**
   * Notificar inicio de ronda
   */
  public emitRoundStarted(roomId: string, roundData: any) {
    this.io.of('/bingo').to(`bingo_room_${roomId}`).emit('round_started', {
      roomId,
      round: roundData
    })
  }

  /**
   * Notificar número sacado
   */
  public emitNumberDrawn(roomId: string, draw: any) {
    this.io.of('/bingo').to(`bingo_room_${roomId}`).emit('number_drawn', {
      roomId,
      number: draw.number,
      letter: draw.letter,
      drawnAt: draw.drawnAt
    })
  }

  /**
   * Notificar ronda finalizada
   */
  public emitRoundEnded(roomId: string, roundData: any) {
    this.io.of('/bingo').to(`bingo_room_${roomId}`).emit('round_ended', {
      roomId,
      round: roundData,
      winners: roundData.winners
    })
  }

  /**
   * Notificar nuevos cartones comprados
   */
  public emitCardsPerchased(roomId: string, purchaseData: any) {
    this.io.of('/bingo').to(`bingo_room_${roomId}`).emit('cards_purchased', {
      roomId,
      userId: purchaseData.userId,
      quantity: purchaseData.quantity
    })
  }

  /**
   * Actualizar estado de sala
   */
  public emitRoomUpdated(roomId: string, roomData: any) {
    this.io.of('/bingo').to(`bingo_room_${roomId}`).emit('room_updated', {
      roomId,
      data: roomData
    })
  }

  /**
   * Obtener usuarios conectados en una sala
   */
  public getConnectedUsersInRoom(roomId: string): Array<{ userId: string, username: string }> {
    const users: Array<{ userId: string, username: string }> = []
    
    this.connectedUsers.forEach((userInfo, socketId) => {
      if (userInfo.rooms.has(roomId)) {
        users.push({
          userId: userInfo.userId,
          username: userInfo.username
        })
      }
    })

    return users
  }

  /**
   * Obtener total de usuarios conectados
   */
  public getConnectedUsersCount(): number {
    return this.connectedUsers.size
  }

  /**
   * Validar token JWT (implementar según tu sistema de auth)
   */
  private validateToken(token: string): string | null {
    try {
      // Aquí implementarías la validación del JWT
      // Por ahora retornamos un ID simulado
      return 'user-id-from-jwt'
    } catch (error) {
      return null
    }
  }
}