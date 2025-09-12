import { z } from 'zod'

// Enums
export enum BingoRoomType {
  NORMAL = 1,
  VIP = 2,
  PREMIUM = 3,
  TOURNAMENT = 4
}

export enum BingoRoundStatus {
  WAITING = 'WAITING',
  IN_PROGRESS = 'IN PROGRESS',
  FINISHED = 'FINISHED',
  CANCELLED = 'CANCELLED'
}

export enum BingoPrizeType {
  FULL_HOUSE = 'FULL_HOUSE',
  SERIAL = 'SERIAL',
  PATTERN = 'PATTERN'
}

// Schemas de validación
export const CreateBingoRoomSchema = z.object({
  roomType: z.nativeEnum(BingoRoomType),
  paidWin: z.boolean().optional(),
  publicationDate: z.date().optional(),
  cardPrice: z.number().min(0),
  maxPlayers: z.number().min(1).max(500).optional(),
  maxCards: z.number().min(1).max(1000).optional(),
  allSale: z.boolean().optional(),
  rkeCommission: z.number().min(0).max(100).optional(),
  roomCommission: z.number().min(0).max(100).optional(),
  sellerCommission: z.number().min(0).max(100).optional(),
  fullHousePrize: z.number().min(0).optional(),
  serialPrize: z.number().min(0).optional()
})

export const UpdateBingoRoomSchema = CreateBingoRoomSchema.partial()

export const JoinRoomSchema = z.object({
  roomId: z.string().uuid(),
  userId: z.string().uuid()
})

export const CreateBingoRoundSchema = z.object({
  roomId: z.string().uuid(),
  round: z.number().min(1),
  bingoPatternId: z.string().uuid().optional(),
  prizeType: z.nativeEnum(BingoPrizeType),
  prizeValue: z.number().min(0)
})

export const DrawNumberSchema = z.object({
  roomId: z.string().uuid(),
  roundId: z.string().uuid(),
  number: z.number().min(1).max(75)
})

export const BuyCardsSchema = z.object({
  roomId: z.string().uuid(),
  quantity: z.number().min(1).max(50),
  currencyId: z.string().uuid()
})

// Tipos derivados de los schemas
export type CreateBingoRoomRequest = z.infer<typeof CreateBingoRoomSchema>
export type UpdateBingoRoomRequest = z.infer<typeof UpdateBingoRoomSchema>
export type JoinRoomRequest = z.infer<typeof JoinRoomSchema>
export type CreateBingoRoundRequest = z.infer<typeof CreateBingoRoundSchema>
export type DrawNumberRequest = z.infer<typeof DrawNumberSchema>
export type BuyCardsRequest = z.infer<typeof BuyCardsSchema>

// Interfaces de respuesta
export interface BingoRoomResponse {
  id: string
  encrypted: string
  uuidCode: string
  status: number
  roomType: BingoRoomType
  roomTypeDescription: string
  creator: {
    id: string
    username: string
    email?: string
  }
  paidWin: boolean
  publicationDate: Date
  cardPrice: number
  players: number
  ctns: number
  allSale: boolean
  rkeCommn: number
  roomCommn: number
  sellerCommn: number
  prizePool: number
  fullHousePrize: number
  serialPrize: number
  closeRoom: boolean
  currentPlayers: number
  currentCards: number
  config?: BingoRoomConfig
  createdAt: Date
  updatedAt: Date
}

export interface BingoRoomConfig {
  id: string
  roomId: string
  date: Date
  time: string
  unixDate: number
  duration: number
  ballInterval: number
  createdAt: Date
  updatedAt: Date
}

export interface BingoRoomListResponse {
  rooms: BingoRoomResponse[]
  total: number
  page: number
  totalPages: number
}

export interface BingoRoundResponse {
  id: string
  roomId: string
  round: number
  startTime: Date | null
  lastChip: number | null
  bingoPatternId: string | null
  prizeType: BingoPrizeType
  prizeValue: number
  status: BingoRoundStatus
  drawnNumbers: number[]
  winners: BingoWinnerResponse[]
  createdAt: Date
  updatedAt: Date
}

export interface BingoCardResponse {
  id: string
  roomId: string
  userId: string
  serialNumber: string
  cardMatrix: BingoCardMatrix
  isActive: boolean
  purchaseDate: Date
  price: number
  currencyId: string
  lot: {
    id: string
    serial: string
  }
}

export interface BingoCardMatrix {
  B: [number, number, number, number, number]
  I: [number, number, number, number, number]
  N: [number, number, number, number, number] // N3 es el centro libre (0)
  G: [number, number, number, number, number]
  O: [number, number, number, number, number]
}

export interface BingoWinnerResponse {
  id: string
  userId: string
  username: string
  cardId: string
  cardSerial: string
  prizeType: BingoPrizeType
  prizeAmount: number
  winTime: Date
  pattern?: string
}

export interface BingoDrawResponse {
  id: string
  roomId: string
  roundId: string
  number: number
  letter: 'B' | 'I' | 'N' | 'G' | 'O'
  drawnAt: Date
  drawnBy: string
}

export interface BingoGameStats {
  room: {
    id: string
    name: string
    totalPlayers: number
    totalCards: number
    prizePool: number
  }
  currentRound: {
    id: string
    number: number
    status: BingoRoundStatus
    drawnNumbers: number[]
    timeElapsed: number
  }
  nextDraw: {
    scheduled: boolean
    timeRemaining: number
  }
}

// WebSocket Event Types
export interface BingoWebSocketEvents {
  'room:join': { roomId: string, userId: string, username: string }
  'room:leave': { roomId: string, userId: string, username: string }
  'round:start': { roomId: string, roundId: string, round: number }
  'round:end': { roomId: string, roundId: string, winners: BingoWinnerResponse[] }
  'number:drawn': { roomId: string, roundId: string, number: number, letter: string }
  'bingo:claimed': { roomId: string, userId: string, cardId: string, prizeType: BingoPrizeType }
  'card:purchased': { roomId: string, userId: string, cardId: string, quantity: number }
  'room:updated': { roomId: string, data: Partial<BingoRoomResponse> }
  'player:status': { roomId: string, userId: string, status: string }
}

// Error Types específicos para Bingo
export enum BingoErrorCode {
  ROOM_NOT_FOUND = 'ROOM_NOT_FOUND',
  ROOM_FULL = 'ROOM_FULL',
  ROOM_CLOSED = 'ROOM_CLOSED',
  INSUFFICIENT_FUNDS = 'INSUFFICIENT_FUNDS',
  INVALID_BINGO_CLAIM = 'INVALID_BINGO_CLAIM',
  ROUND_NOT_ACTIVE = 'ROUND_NOT_ACTIVE',
  CARD_NOT_FOUND = 'CARD_NOT_FOUND',
  ALREADY_IN_ROOM = 'ALREADY_IN_ROOM',
  UNAUTHORIZED_ACTION = 'UNAUTHORIZED_ACTION',
  GAME_NOT_STARTED = 'GAME_NOT_STARTED'
}