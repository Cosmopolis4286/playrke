import { computed } from 'vue'
import type { ComputedRef, Ref } from 'vue'
import type { BingoRoom } from '@/js/types/games/bingo/room/bingo-room'
import { useBingoRoomTimeline, type TimelineSlot } from './useBingoRoomTimeline'

export interface ConflictResult {
    hasConflict:   boolean
    conflictingRooms: TimelineSlot[]
    gapMinutes:    number
}

/**
 * Detecta si una sala candidata (definida por unix_date) solapa con otras salas
 * ya programadas dentro de la ventana de la línea de tiempo.
 *
 * Uso:
 *   const { conflict } = useBingoRoomConflictDetector(rooms, candidateUnixDate, 30)
 *
 * @param rooms             Lista reactiva de salas cargadas (misma que el timeline).
 * @param candidateUnixDate unix_date (segundos) de la sala que se quiere crear/editar.
 * @param gapMinutes        Minutos mínimos de separación entre salas (default 30).
 * @param excludeRoomId     ID de sala a excluir del chequeo (para edición).
 */
export function useBingoRoomConflictDetector(
    rooms:              Ref<BingoRoom[]> | ComputedRef<BingoRoom[]>,
    candidateUnixDate:  Ref<number | null> | ComputedRef<number | null>,
    gapMinutes          = 30,
    excludeRoomId:      Ref<string | null> | ComputedRef<string | null> = computed(() => null),
): { conflict: ComputedRef<ConflictResult> } {
    const { days } = useBingoRoomTimeline(rooms)

    const conflict = computed<ConflictResult>(() => {
        const unix = candidateUnixDate.value
        if (!unix) {
            return { hasConflict: false, conflictingRooms: [], gapMinutes }
        }

        const candidateDate  = new Date(unix * 1000)
        const candidateDayKey = candidateDate.toISOString().slice(0, 10)
        const candidateMinute = candidateDate.getHours() * 60 + candidateDate.getMinutes()

        const day = days.value.find(d => d.date.toISOString().slice(0, 10) === candidateDayKey)
        if (!day) {
            return { hasConflict: false, conflictingRooms: [], gapMinutes }
        }

        const exclude = excludeRoomId.value
        const conflicting = day.slots.filter(slot => {
            if (exclude && slot.roomId === exclude) return false
            return Math.abs(slot.minuteOfDay - candidateMinute) < gapMinutes
        })

        return {
            hasConflict:      conflicting.length > 0,
            conflictingRooms: conflicting,
            gapMinutes,
        }
    })

    return { conflict }
}
