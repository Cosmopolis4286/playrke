import type { RoomCountdown } from '@js/composables/games/bingo/room/game-rooms/useGameRoomCountdown'

export const BINGO_ROOM_IMG = '/img/games/bingo/estelar_fd.png'

export const GAME_MODE_LABELS: Record<string, string> = {
  ONLINE:  'Online',
  OFFLINE: 'Presencial',
  MIXED:   'Mixto',
}

export const ROOM_TYPE_LABELS: Record<string, string> = {
  PUBLIC:  'Pública',
  PRIVATE: 'Privada',
  VIP:     'VIP',
  DEMO:    'Demo',
}

export const GAME_VARIANT_LABELS: Record<string, string> = {
  BINGO_75: 'Bingo 75 bolas',
  BINGO_90: 'Bingo 90 bolas',
  BINGO_80: 'Bingo 80 bolas',
  BINGO_30: 'Bingo 30 bolas (Speed)',
}

/** prize_pool_type: código de la relación prize_pool_types (ACCUMULATIVE/FIXED/MIXED/PRIZE) */
export function isAccumulatedPrize(prizePoolType?: string | null): boolean {
  return prizePoolType === 'ACCUMULATIVE' || prizePoolType === 'MIXED'
}

export function formatCountdown(cd: RoomCountdown | undefined): string {
  if (!cd || cd.expired) return 'EN VIVO'
  const { days, hours, minutes, seconds } = cd.time
  if (days > 0)   return `${days}d ${String(hours).padStart(2, '0')}h ${String(minutes).padStart(2, '0')}m`
  if (hours > 0)  return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
  return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
}

export function isLive(cd: RoomCountdown | undefined): boolean {
  return !cd || cd.expired
}
