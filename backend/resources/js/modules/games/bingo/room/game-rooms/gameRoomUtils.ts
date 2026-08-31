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

/**
 * "Abierta hace X" — usado como fallback en el footer de la card cuando la
 * sala no tiene un mínimo de cartones configurado (segunda línea bajo el
 * precio, que si no se rellena queda vacía). No es un countdown reactivo:
 * se recalcula solo cuando Vue re-renderiza el componente (suficiente para
 * un dato de granularidad "minutos/horas", no necesita setInterval propio).
 */
export function formatOpenSince(unixCreatedAt: number | null | undefined): string | null {
  if (!unixCreatedAt) return null
  const elapsed = Math.max(0, Math.floor(Date.now() / 1000) - unixCreatedAt)

  if (elapsed < 60) return 'Abierta recién'

  const minutes = Math.floor(elapsed / 60)
  if (minutes < 60) return `Abierta hace ${minutes}m`

  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `Abierta hace ${hours}h`

  const days = Math.floor(hours / 24)
  return `Abierta hace ${days}d`
}

/**
 * Código corto de sala para mostrar en UI — últimos 6 caracteres del
 * uuid_code, en mayúsculas. Antes duplicado (con la misma lógica repetida
 * en cada componente que lo necesitaba: RoomCardThumbnail, BingoRoomInfoModal),
 * lo que dejó el modal de Top de Compras sin truncar por accidente al
 * añadirse sin reutilizar este criterio.
 */
export function shortRoomCode(uuidCode: string | null | undefined): string | null {
  if (!uuidCode) return null
  return uuidCode.length > 6 ? uuidCode.slice(-6).toUpperCase() : uuidCode.toUpperCase()
}

export type TimerLevel = 'ok' | 'warning' | 'urgent'

/**
 * Nivel de urgencia del countdown, tipo semáforo — usado por la card y el modal
 * de info para colorear el badge del timer según el tiempo restante:
 * verde (>10min) → amarillo (2-10min) → rojo (<2min).
 */
export function getTimerLevel(cd: RoomCountdown | undefined): TimerLevel {
  if (!cd || cd.expired) return 'ok'
  const { days, hours, minutes } = cd.time
  if (days > 0 || hours > 0) return 'ok'
  if (minutes < 2) return 'urgent'
  if (minutes < 10) return 'warning'
  return 'ok'
}
