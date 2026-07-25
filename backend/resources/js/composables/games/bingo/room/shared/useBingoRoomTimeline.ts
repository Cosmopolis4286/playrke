import { computed } from 'vue'
import type { ComputedRef, Ref } from 'vue'
import type { BingoRoom } from '@/js/types/games/bingo/room/bingo-room'

export interface TimelineSlot {
    roomId:    string
    uuidCode:  string | null
    startAt:   Date
    status:    string
    startMode: string
    /** Minutos desde el inicio del día (0–1439) */
    minuteOfDay: number
}

export interface TimelineDay {
    date:  Date
    label: string
    slots: TimelineSlot[]
}

/**
 * Agrupa salas de bingo por día de inicio (unix_date) para renderizar
 * una vista de línea de tiempo (gantt diario).
 *
 * Solo incluye salas con unix_date y status no-terminal (no DELETED/FINISHED).
 *
 * Uso:
 *   const { days, slotsForDate, hasOverlap } = useBingoRoomTimeline(rooms, 7)
 *
 * @param rooms      Ref/ComputedRef de la lista de salas cargadas.
 * @param windowDays Cuántos días mostrar hacia adelante (default 7).
 */
export function useBingoRoomTimeline(
    rooms: Ref<BingoRoom[]> | ComputedRef<BingoRoom[]>,
    windowDays = 7,
) {
    const TERMINAL = new Set(['DELETED', 'FINISHED', 'CANCELLED'])

    const days: ComputedRef<TimelineDay[]> = computed(() => {
        const now   = new Date()
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())

        const dayMap = new Map<string, TimelineSlot[]>()

        // Pre-populate the window so days without rooms still appear
        for (let i = 0; i < windowDays; i++) {
            const d = new Date(today)
            d.setDate(today.getDate() + i)
            dayMap.set(d.toISOString().slice(0, 10), [])
        }

        for (const room of rooms.value) {
            const config = (room as any).config
            const unixDate: number | null | undefined = config?.unix_date ?? (room as any).unix_date
            const statusCode: string = room.status ?? ''

            if (!unixDate || TERMINAL.has(statusCode)) continue

            const startAt = new Date(unixDate * 1000)
            const dayKey  = startAt.toISOString().slice(0, 10)

            if (!dayMap.has(dayKey)) continue

            dayMap.get(dayKey)!.push({
                roomId:      String(room.id),
                uuidCode:    room.uuid_code ?? null,
                startAt,
                status:      statusCode,
                startMode:   config?.start_mode ?? 'SCHEDULED',
                minuteOfDay: startAt.getHours() * 60 + startAt.getMinutes(),
            })
        }

        const result: TimelineDay[] = []
        dayMap.forEach((slots, key) => {
            const date = new Date(key + 'T00:00:00')
            result.push({
                date,
                label: date.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' }),
                slots: slots.sort((a, b) => a.minuteOfDay - b.minuteOfDay),
            })
        })

        return result.sort((a, b) => a.date.getTime() - b.date.getTime())
    })

    function slotsForDate(date: Date): TimelineSlot[] {
        const key = date.toISOString().slice(0, 10)
        return days.value.find(d => d.date.toISOString().slice(0, 10) === key)?.slots ?? []
    }

    /** Detecta solapamientos: dos salas que empiezan con menos de N minutos de diferencia. */
    function hasOverlap(slots: TimelineSlot[], gapMinutes = 30): boolean {
        for (let i = 1; i < slots.length; i++) {
            if (slots[i].minuteOfDay - slots[i - 1].minuteOfDay < gapMinutes) return true
        }
        return false
    }

    return { days, slotsForDate, hasOverlap }
}
