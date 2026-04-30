// Room Types
export interface Room {
  id: string
  code: string
  title: string
  description?: string
  category?: string
  hostId: string
  status: 'waiting' | 'playing' | 'paused' | 'finished' | 'cancelled'
  maxPlayers: number
  questionCount: number
  timePerQuestion: number
  currentPlayers: number
  scheduledAt?: string
  entryFee?: number | null
  entryCurrencyId?: string | null
  entryCurrencySymbol?: string | null
  prizeAmount?: number | null
  prizeCurrencyId?: string | null
  prizeType?: 'fixed' | 'accumulated' | null
  createdAt: string
  updatedAt: string
}

// Player Types
export interface Player {
  id: string
  name: string
  roomCode: string
  joinedAt?: string | null   // ISO timestamp de inscripción
  score?: number
  correctAnswers?: number
  wrongAnswers?: number
  averageResponseTime?: number
  maxStreak?: number
  position?: number
  isHost?: boolean
}

// Question Types
export interface Question {
  id: string
  questionText: string
  imageUrl?: string
  options: string[]
  correctOptionIndex: number
  timeLimit: number
  points: number
  category?: string
  difficulty: 'easy' | 'medium' | 'hard'
  orderIndex: number
  totalQuestions: number
}

// Game Types
export type GameState = 'waiting' | 'answering' | 'closed' | 'finished'

export interface Game {
  id: string
  roomId: string
  gameCode: string
  status: 'playing' | 'paused' | 'finished' | 'cancelled'
  currentQuestionIndex: number
  totalQuestions: number
  playerCount: number
  timePerQuestion: number
  winnerUserId?: string
  startedAt?: string
  endedAt?: string
}

// Answer Types
export interface Answer {
  id: string
  gameId: string
  playerId: string
  questionId: string
  selectedOptionIndex: number
  isCorrect: boolean
  pointsEarned: number
  responseTimeMs: number
  isLate: boolean
  clientId?: string
  createdAt: string
}

// Score Types
export interface Score {
  id: string
  gameId: string
  playerId: string
  finalScore: number
  correctAnswers: number
  wrongAnswers: number
  averageResponseTimeMs: number
  position?: number
  maxStreak: number
  isWinner: boolean
  totalQuestions: number
  totalResponseTimeMs: number
  metadata?: Record<string, any>
}

// Game Stats
export interface GameStats {
  score: number
  correctAnswers: number
  wrongAnswers: number
  accuracy: number
  averageResponseTime: number
  position?: number
  maxStreak?: number
}

// DTOs
export interface RoomDTO {
  id: string
  code: string
  title: string
  description?: string
  hostId: string
  status: string
  maxPlayers: number
  questionCount: number
  timePerQuestion: number
  currentPlayers: number
}

export interface PlayerDTO {
  id: string
  name: string
  roomCode: string
}

// WebSocket Event Types
export interface GameEvent {
  type: 'question.sent' | 'question.closed' | 'answer.received' | 'leaderboard.updated' | 'game.started' | 'game.finished' | 'player.joined' | 'player.left' | 'room.updated'
  [key: string]: any
}

// API Response Types
export interface ApiResponse<T> {
  data: T
  message?: string
}

export interface ApiError {
  message: string
  errors?: Record<string, string[]>
}
