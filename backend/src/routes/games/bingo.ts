import { FastifyInstance } from 'fastify'
import { BingoRoomController } from '../../controllers/games/bingo/BingoRoomController'
import { BingoCardController } from '../../controllers/games/bingo/BingoCardController'
import { BingoRoundController } from '../../controllers/games/bingo/BingoRoundController'
import { authenticateUser } from '../../middleware/auth'

export async function bingoRoutes(fastify: FastifyInstance) {
  const roomController = new BingoRoomController()
  const cardController = new BingoCardController()
  const roundController = new BingoRoundController()

  // Aplicar middleware de autenticación a todas las rutas
  fastify.addHook('onRequest', authenticateUser)

  // === RUTAS DE SALAS DE BINGO ===
  
  // Obtener tipos de sala
  fastify.get('/room-types', {
    schema: {
      tags: ['Bingo - Rooms'],
      summary: 'Obtener tipos de sala disponibles',
      response: {
        200: {
          description: 'Tipos de sala obtenidos exitosamente',
          type: 'object',
          properties: {
            success: { type: 'boolean' },
            data: {
              type: 'array',
              items: {
                type: 'object',
                properties: {
                  id: { type: 'number' },
                  name: { type: 'string' },
                  description: { type: 'string' }
                }
              }
            },
            message: { type: 'string' }
          }
        }
      }
    }
  }, roomController.getRoomTypes.bind(roomController))

  // Listar salas disponibles
  fastify.get('/rooms', {
    schema: {
      tags: ['Bingo - Rooms'],
      summary: 'Listar salas de bingo disponibles',
      querystring: {
        type: 'object',
        properties: {
          roomType: { type: 'number', description: 'Filtrar por tipo de sala' },
          creatorId: { type: 'string', description: 'Filtrar por creador' },
          limit: { type: 'number', default: 20, description: 'Límite de resultados' },
          offset: { type: 'number', default: 0, description: 'Offset para paginación' }
        }
      },
      response: {
        200: {
          description: 'Salas obtenidas exitosamente',
          type: 'object',
          properties: {
            success: { type: 'boolean' },
            data: {
              type: 'object',
              properties: {
                rooms: { type: 'array' },
                total: { type: 'number' },
                page: { type: 'number' },
                totalPages: { type: 'number' }
              }
            },
            message: { type: 'string' }
          }
        }
      }
    }
  }, roomController.listRooms.bind(roomController))

  // Obtener mis salas creadas
  fastify.get('/rooms/my', {
    schema: {
      tags: ['Bingo - Rooms'],
      summary: 'Obtener mis salas creadas',
      querystring: {
        type: 'object',
        properties: {
          limit: { type: 'number', default: 20 },
          offset: { type: 'number', default: 0 }
        }
      }
    }
  }, roomController.getMyRooms.bind(roomController))

  // Crear nueva sala
  fastify.post('/rooms', {
    schema: {
      tags: ['Bingo - Rooms'],
      summary: 'Crear nueva sala de bingo',
      body: {
        type: 'object',
        required: ['roomType', 'cardPrice'],
        properties: {
          roomType: { type: 'number', enum: [1, 2, 3, 4] },
          paidWin: { type: 'boolean' },
          publicationDate: { type: 'string', format: 'date-time' },
          cardPrice: { type: 'number', minimum: 0 },
          maxPlayers: { type: 'number', minimum: 1, maximum: 500 },
          maxCards: { type: 'number', minimum: 1, maximum: 1000 },
          allSale: { type: 'boolean' },
          rkeCommission: { type: 'number', minimum: 0, maximum: 100 },
          roomCommission: { type: 'number', minimum: 0, maximum: 100 },
          sellerCommission: { type: 'number', minimum: 0, maximum: 100 },
          fullHousePrize: { type: 'number', minimum: 0 },
          serialPrize: { type: 'number', minimum: 0 }
        }
      },
      response: {
        201: {
          description: 'Sala creada exitosamente',
          type: 'object',
          properties: {
            success: { type: 'boolean' },
            data: { type: 'object' },
            message: { type: 'string' }
          }
        }
      }
    }
  }, roomController.createRoom.bind(roomController))

  // Obtener sala por ID
  fastify.get('/rooms/:id', {
    schema: {
      tags: ['Bingo - Rooms'],
      summary: 'Obtener sala por ID',
      params: {
        type: 'object',
        properties: {
          id: { type: 'string', format: 'uuid' }
        },
        required: ['id']
      }
    }
  }, roomController.getRoomById.bind(roomController))

  // Obtener sala por código
  fastify.get('/rooms/code/:encrypted', {
    schema: {
      tags: ['Bingo - Rooms'],
      summary: 'Obtener sala por código encriptado',
      params: {
        type: 'object',
        properties: {
          encrypted: { type: 'string' }
        },
        required: ['encrypted']
      }
    }
  }, roomController.getRoomByCode.bind(roomController))

  // Actualizar sala
  fastify.put('/rooms/:id', {
    schema: {
      tags: ['Bingo - Rooms'],
      summary: 'Actualizar sala de bingo',
      params: {
        type: 'object',
        properties: {
          id: { type: 'string', format: 'uuid' }
        },
        required: ['id']
      },
      body: {
        type: 'object',
        properties: {
          roomType: { type: 'number', enum: [1, 2, 3, 4] },
          paidWin: { type: 'boolean' },
          cardPrice: { type: 'number', minimum: 0 },
          maxPlayers: { type: 'number', minimum: 1, maximum: 500 },
          maxCards: { type: 'number', minimum: 1, maximum: 1000 },
          fullHousePrize: { type: 'number', minimum: 0 },
          serialPrize: { type: 'number', minimum: 0 },
          closeRoom: { type: 'boolean' }
        }
      }
    }
  }, roomController.updateRoom.bind(roomController))

  // Unirse a sala
  fastify.post('/rooms/:id/join', {
    schema: {
      tags: ['Bingo - Rooms'],
      summary: 'Unirse a una sala de bingo',
      params: {
        type: 'object',
        properties: {
          id: { type: 'string', format: 'uuid' }
        },
        required: ['id']
      }
    }
  }, roomController.joinRoom.bind(roomController))

  // Cerrar/eliminar sala
  fastify.delete('/rooms/:id', {
    schema: {
      tags: ['Bingo - Rooms'],
      summary: 'Cerrar sala de bingo',
      params: {
        type: 'object',
        properties: {
          id: { type: 'string', format: 'uuid' }
        },
        required: ['id']
      }
    }
  }, roomController.deleteRoom.bind(roomController))

  // === RUTAS DE CARTONES ===

  // Comprar cartones
  fastify.post('/cards/buy', {
    schema: {
      tags: ['Bingo - Cards'],
      summary: 'Comprar cartones para una sala',
      body: {
        type: 'object',
        required: ['roomId', 'quantity', 'currencyId'],
        properties: {
          roomId: { type: 'string', format: 'uuid' },
          quantity: { type: 'number', minimum: 1, maximum: 50 },
          currencyId: { type: 'string', format: 'uuid' }
        }
      }
    }
  }, cardController.buyCards.bind(cardController))

  // Obtener mis cartones en una sala
  fastify.get('/rooms/:roomId/cards', {
    schema: {
      tags: ['Bingo - Cards'],
      summary: 'Obtener mis cartones en una sala',
      params: {
        type: 'object',
        properties: {
          roomId: { type: 'string', format: 'uuid' }
        },
        required: ['roomId']
      }
    }
  }, cardController.getMyCardsInRoom.bind(cardController))

  // Obtener cartón por ID
  fastify.get('/cards/:id', {
    schema: {
      tags: ['Bingo - Cards'],
      summary: 'Obtener cartón por ID',
      params: {
        type: 'object',
        properties: {
          id: { type: 'string', format: 'uuid' }
        },
        required: ['id']
      }
    }
  }, cardController.getCardById.bind(cardController))

  // Validar BINGO
  fastify.post('/cards/:id/validate', {
    schema: {
      tags: ['Bingo - Cards'],
      summary: 'Validar BINGO en un cartón',
      params: {
        type: 'object',
        properties: {
          id: { type: 'string', format: 'uuid' }
        },
        required: ['id']
      },
      body: {
        type: 'object',
        required: ['drawnNumbers'],
        properties: {
          drawnNumbers: {
            type: 'array',
            items: { type: 'number', minimum: 1, maximum: 75 }
          },
          claimedPattern: { type: 'string' }
        }
      }
    }
  }, cardController.validateBingo.bind(cardController))

  // Desactivar cartón
  fastify.put('/cards/:id/deactivate', {
    schema: {
      tags: ['Bingo - Cards'],
      summary: 'Desactivar cartón',
      params: {
        type: 'object',
        properties: {
          id: { type: 'string', format: 'uuid' }
        },
        required: ['id']
      }
    }
  }, cardController.deactivateCard.bind(cardController))

  // Estadísticas de cartones en sala
  fastify.get('/rooms/:roomId/cards/stats', {
    schema: {
      tags: ['Bingo - Cards'],
      summary: 'Estadísticas de cartones en sala',
      params: {
        type: 'object',
        properties: {
          roomId: { type: 'string', format: 'uuid' }
        },
        required: ['roomId']
      }
    }
  }, cardController.getCardStats.bind(cardController))

  // === RUTAS DE RONDAS ===

  // Obtener tipos de premio
  fastify.get('/prize-types', {
    schema: {
      tags: ['Bingo - Rounds'],
      summary: 'Obtener tipos de premio disponibles'
    }
  }, roundController.getPrizeTypes.bind(roundController))

  // Crear ronda
  fastify.post('/rounds', {
    schema: {
      tags: ['Bingo - Rounds'],
      summary: 'Crear nueva ronda de bingo',
      body: {
        type: 'object',
        required: ['roomId', 'round', 'prizeType', 'prizeValue'],
        properties: {
          roomId: { type: 'string', format: 'uuid' },
          round: { type: 'number', minimum: 1 },
          bingoPatternId: { type: 'string', format: 'uuid' },
          prizeType: { type: 'string', enum: ['FULL_HOUSE', 'SERIAL', 'PATTERN'] },
          prizeValue: { type: 'number', minimum: 0 }
        }
      }
    }
  }, roundController.createRound.bind(roundController))

  // Iniciar ronda
  fastify.put('/rounds/:id/start', {
    schema: {
      tags: ['Bingo - Rounds'],
      summary: 'Iniciar ronda de bingo',
      params: {
        type: 'object',
        properties: {
          id: { type: 'string', format: 'uuid' }
        },
        required: ['id']
      }
    }
  }, roundController.startRound.bind(roundController))

  // Sacar número
  fastify.post('/rounds/:id/draw', {
    schema: {
      tags: ['Bingo - Rounds'],
      summary: 'Sacar número en una ronda',
      params: {
        type: 'object',
        properties: {
          id: { type: 'string', format: 'uuid' }
        },
        required: ['id']
      },
      body: {
        type: 'object',
        required: ['roomId', 'number'],
        properties: {
          roomId: { type: 'string', format: 'uuid' },
          number: { type: 'number', minimum: 1, maximum: 75 }
        }
      }
    }
  }, roundController.drawNumber.bind(roundController))

  // Reclamar BINGO
  fastify.post('/rounds/:id/claim', {
    schema: {
      tags: ['Bingo - Rounds'],
      summary: 'Reclamar BINGO',
      params: {
        type: 'object',
        properties: {
          id: { type: 'string', format: 'uuid' }
        },
        required: ['id']
      },
      body: {
        type: 'object',
        required: ['cardId'],
        properties: {
          cardId: { type: 'string', format: 'uuid' },
          claimedPattern: { type: 'string' }
        }
      }
    }
  }, roundController.claimBingo.bind(roundController))

  // Finalizar ronda
  fastify.put('/rounds/:id/end', {
    schema: {
      tags: ['Bingo - Rounds'],
      summary: 'Finalizar ronda de bingo',
      params: {
        type: 'object',
        properties: {
          id: { type: 'string', format: 'uuid' }
        },
        required: ['id']
      }
    }
  }, roundController.endRound.bind(roundController))

  // Obtener ronda actual de una sala
  fastify.get('/rooms/:roomId/current-round', {
    schema: {
      tags: ['Bingo - Rounds'],
      summary: 'Obtener ronda actual de una sala',
      params: {
        type: 'object',
        properties: {
          roomId: { type: 'string', format: 'uuid' }
        },
        required: ['roomId']
      }
    }
  }, roundController.getCurrentRound.bind(roundController))

  // Obtener historial de rondas
  fastify.get('/rooms/:roomId/rounds/history', {
    schema: {
      tags: ['Bingo - Rounds'],
      summary: 'Obtener historial de rondas de una sala',
      params: {
        type: 'object',
        properties: {
          roomId: { type: 'string', format: 'uuid' }
        },
        required: ['roomId']
      },
      querystring: {
        type: 'object',
        properties: {
          limit: { type: 'number', default: 10, maximum: 50 }
        }
      }
    }
  }, roundController.getRoundsHistory.bind(roundController))

  // Obtener ronda por ID
  fastify.get('/rounds/:id', {
    schema: {
      tags: ['Bingo - Rounds'],
      summary: 'Obtener información de una ronda',
      params: {
        type: 'object',
        properties: {
          id: { type: 'string', format: 'uuid' }
        },
        required: ['id']
      }
    }
  }, roundController.getRoundById.bind(roundController))

  // Estadísticas de ronda
  fastify.get('/rounds/:id/stats', {
    schema: {
      tags: ['Bingo - Rounds'],
      summary: 'Obtener estadísticas de una ronda',
      params: {
        type: 'object',
        properties: {
          id: { type: 'string', format: 'uuid' }
        },
        required: ['id']
      }
    }
  }, roundController.getRoundStats.bind(roundController))
}