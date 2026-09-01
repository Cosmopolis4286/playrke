// ── Room ──────────────────────────────────────────────────────────────────────
export type GameMode = 'free_for_all' | 'teams' | 'duel'

export interface Team {
  id: string
  name: string
  color?: string
  icon?: string
  maxPlayers: number
  currentPlayers: number
}

export interface Room {
  id: string
  code: string
  title: string
  description?: string
  category?: string
  hostId: string
  status: 'waiting' | 'playing' | 'paused' | 'finished' | 'cancelled'
  gameMode?: GameMode
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

// ── Player ────────────────────────────────────────────────────────────────────
export interface Player {
  id: string
  name: string
  roomCode: string
  teamId?: string | null
  joinedAt?: string | null
  score?: number
  correctAnswers?: number
  wrongAnswers?: number
  averageResponseTime?: number
  maxStreak?: number
  position?: number
  isHost?: boolean
}

// ── Question ──────────────────────────────────────────────────────────────────
export interface Question {
  id: string
  text: string
  image?: string | null
  options: string[]
  correctOptionIndex: number
  timeLimit: number
  points: number
  category?: string
  difficulty?: 'easy' | 'medium' | 'hard'
  orderIndex?: number
  totalQuestions?: number
}

// ── Game ──────────────────────────────────────────────────────────────────────
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

// ── Answer ────────────────────────────────────────────────────────────────────
export interface AnswerPayload {
  question_id: string
  selected_option_index: number   // -1 = tiempo agotado
  response_time_ms: number
  client_id?: string
}

export interface AnswerResult {
  success: boolean
  is_correct: boolean
  is_late: boolean
  points_earned: number
  correct_option_index: number
  current_score: number
}

// ── Power-ups ─────────────────────────────────────────────────────────────────
export type PowerUpType = 'fifty_fifty' | 'double_points' | 'skip' | 'extra_time'

/** Usos restantes por tipo (0 si ya se usó, ausente el tipo si la sala no lo habilita). */
export type PowerUpsAvailable = Partial<Record<PowerUpType, number>>

export interface PowerUpResponse {
  success: boolean
  message?: string
  type?: PowerUpType
  hidden_indexes?: number[]  // solo fifty_fifty
  bonus_ms?: number          // solo extra_time
}

// ── Score ─────────────────────────────────────────────────────────────────────
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
}

/** Estadísticas de un jugador para el resumen de resultados (PlayerScore.vue, Results.vue). */
export interface GameStats {
  score: number
  correctAnswers: number
  wrongAnswers: number
  accuracy: number
  averageResponseTime: number
  maxStreak?: number
  position?: number
}

// ── WebSocket events (broadcastWith payloads) ─────────────────────────────────
export interface WsPlayerJoined {
  player: { id: string; name: string; teamId?: string | null }
  current_player_count: number
}

export interface WsPlayerLeft {
  player_id: string
  current_player_count: number
  reason: string
}

export interface WsGameStarted {
  game_id: string
  room_code: string
  total_questions: number
  time_per_question: number
}

export interface WsQuestionSent {
  game_id: string
  question_index: number
  total_questions: number
  question: Question
  server_timestamp: number  // ms epoch — para compensar latencia de red en el timer
}

export interface WsAnswerResult {
  game_id: string
  question_id: string
  is_correct: boolean
  points_earned: number
  correct_option_index: number
  current_score: number
}

export interface WsGameFinished {
  game_id: string
  leaderboard: Array<{ player_id: string; name: string; score: number; position: number }>
}

export interface WsGameCancelled {
  game_id: string
  reason: string
}

export interface WsGamePaused {
  game_id: string
  reason: string
}

export interface WsGameResumed {
  game_id: string
  remaining_seconds: number
}

// ── API response helpers ──────────────────────────────────────────────────────
export interface ApiResponse<T> {
  data: T
  message?: string
}

export interface ApiError {
  message: string
  errors?: Record<string, string[]>
}

// ── DTOs (internal) ───────────────────────────────────────────────────────────
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
