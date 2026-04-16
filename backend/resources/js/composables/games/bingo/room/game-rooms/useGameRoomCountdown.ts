import { ref, onUnmounted } from 'vue'
import type { BingoGameRoom } from '@/js/types/games/bingo/room/game-rooms/bingo-game-room'

// ---------------------------------------------------------------------------
// Constantes de tiempo
// ---------------------------------------------------------------------------

const SECOND = 1_000
const MINUTE = SECOND * 60
const HOUR = MINUTE * 60
const DAY = HOUR * 24

// ---------------------------------------------------------------------------
// Tipos locales
// ---------------------------------------------------------------------------

/**
 * Tiempo restante desglosado en unidades.
 */
export interface TimeRemaining {
  days: number
  hours: number
  minutes: number
  seconds: number
}

/**
 * Estado del countdown para una sala.
 */
export interface RoomCountdown {
  /** ID de la sala */
  roomId: string
  /** Tiempo restante calculado */
  time: TimeRemaining
  /** Si el tiempo ya expiró */
  expired: boolean
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Calcula el tiempo restante a partir de una diferencia en ms.
 * @param diff - Diferencia en milisegundos
 */
function calcTimeRemaining(diff: number): TimeRemaining {
  return {
    days: Math.floor(diff / DAY),
    hours: Math.floor((diff % DAY) / HOUR),
    minutes: Math.floor((diff % HOUR) / MINUTE),
    seconds: Math.floor((diff % MINUTE) / SECOND),
  }
}

// ---------------------------------------------------------------------------
// Composable
// ---------------------------------------------------------------------------

/**
 * Composable que gestiona los countdowns reactivos de las salas de juego.
 *
 * - Acepta un timestamp base del servidor para sincronizar el reloj.
 * - Emite `onExpire(roomId)` cuando una sala expira.
 * - Se limpia automáticamente al desmontar el componente.
 *
 * @param serverTimestampMs - Timestamp del servidor en milisegundos
 * @param onExpire - Callback llamado cuando una sala expira
 */
export function useGameRoomCountdown(
  serverTimestampMs: number,
  onExpire?: (roomId: string) => void,
) {
  /** Reloj local sincronizado con el servidor */
  const now = ref(serverTimestampMs)

  /** Map reactivo de countdowns por room ID */
  const countdowns = ref<Map<string, RoomCountdown>>(new Map())

  let intervalId: ReturnType<typeof setInterval> | null = null

  /**
   * Registra las salas a monitorear.
   * @param rooms - Lista de salas de juego
   */
  function registerRooms(rooms: BingoGameRoom[]): void {
    const map = new Map<string, RoomCountdown>()

    for (const room of rooms) {
      const unixDate = room.config?.unix_date
      if (!unixDate) continue

      const end = unixDate * 1_000
      const diff = end - now.value

      map.set(room.id, {
        roomId: room.id,
        time: calcTimeRemaining(Math.max(0, diff)),
        expired: diff <= 0,
      })
    }

    countdowns.value = map
  }

  /**
   * Inicia el intervalo de 1 segundo que actualiza todos los countdowns.
   */
  function start(): void {
    if (intervalId !== null) return

    intervalId = setInterval(() => {
      now.value += SECOND

      countdowns.value.forEach((entry, roomId) => {
        if (entry.expired) return

        // Para obtener el unix_date hay que reconstruirlo desde la diferencia.
        // Guardamos el endMs cuando registramos la sala.
        const endMs = _endMs.get(roomId)
        if (endMs === undefined) return

        const diff = endMs - now.value

        if (diff <= 0) {
          countdowns.value.set(roomId, { roomId, time: calcTimeRemaining(0), expired: true })
          onExpire?.(roomId)
          return
        }

        countdowns.value.set(roomId, {
          roomId,
          time: calcTimeRemaining(diff),
          expired: false,
        })
      })
    }, SECOND)
  }

  /** Mapa interno: roomId → endMs (no reactivo, solo lectura interna) */
  const _endMs = new Map<string, number>()

  /**
   * Registra las salas y las guarda en el mapa interno de endMs.
   * Extiende `registerRooms` guardando también el endMs por sala.
   * @param rooms - Lista de salas de juego
   */
  function init(rooms: BingoGameRoom[]): void {
    _endMs.clear()

    for (const room of rooms) {
      const unixDate = room.config?.unix_date
      if (!unixDate) continue
      _endMs.set(room.id, unixDate * 1_000)
    }

    registerRooms(rooms)
    start()
  }

  /** Detiene el intervalo. */
  function stop(): void {
    if (intervalId !== null) {
      clearInterval(intervalId)
      intervalId = null
    }
  }

  /**
   * Obtiene el countdown de una sala específica.
   * @param roomId - ID de la sala
   */
  function getCountdown(roomId: string): RoomCountdown | undefined {
    return countdowns.value.get(roomId)
  }

  /**
   * Sincroniza el tiempo del cliente con el tiempo del servidor.
   * Útil cuando el usuario vuelve a la página después de estar en segundo plano.
   * @param serverTimestampMs - Timestamp actual del servidor en milisegundos
   */
  function syncTime(serverTimestampMs: number): void {
    now.value = serverTimestampMs

    // Recalcular todos los countdowns con el nuevo tiempo
    countdowns.value.forEach((entry, roomId) => {
      const endMs = _endMs.get(roomId)
      if (endMs === undefined) return

      const diff = endMs - now.value

      if (diff <= 0) {
        countdowns.value.set(roomId, { roomId, time: calcTimeRemaining(0), expired: true })
      } else {
        countdowns.value.set(roomId, {
          roomId,
          time: calcTimeRemaining(diff),
          expired: false,
        })
      }
    })
  }

  /**
   * Actualiza el unix_date de una sala específica y recalcula el countdown.
   * @param roomId - ID de la sala
   * @param unixDate - Nuevo timestamp de fin
   */
  function updateRoomTime(roomId: string, unixDate: number): void {
    const endMs = unixDate * 1_000
    _endMs.set(roomId, endMs)

    const diff = endMs - now.value

    if (diff <= 0) {
      countdowns.value.set(roomId, { roomId, time: calcTimeRemaining(0), expired: true })
    } else {
      countdowns.value.set(roomId, {
        roomId,
        time: calcTimeRemaining(diff),
        expired: false,
      })
    }
  }

  onUnmounted(stop)

  return {
    /** Map reactivo de countdowns (roomId → RoomCountdown) */
    countdowns,
    /** Inicializa y arranca el countdown con la lista de salas */
    init,
    /** Detiene el intervalo manualmente */
    stop,
    /** Retorna el countdown de una sala por ID */
    getCountdown,
    /** Sincroniza el tiempo con el servidor */
    syncTime,
    /** Actualiza el tiempo de una sala específica */
    updateRoomTime,
  }
}
