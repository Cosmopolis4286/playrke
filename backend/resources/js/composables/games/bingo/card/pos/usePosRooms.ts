import { computed, onUnmounted, reactive, ref, watch } from 'vue'

import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'
import type { SelectOption } from '@/js/types/common/form/form'
import type { PosRoom } from '@/js/types/games/bingo/card/bingo-card-generate'

/** Fecha LOCAL del vendedor (YYYY-MM-DD) — toISOString() daría la fecha UTC. */
export function localIsoDate(offsetDays = 0): string {
    const d = new Date()
    d.setDate(d.getDate() + offsetDays)
    const pad = (n: number) => String(n).padStart(2, '0')
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
}

export type RoomFilterKey = 'room_type_id' | 'connection_type_id' | 'prize_pool_type_id'

interface FilterChip {
    value: string
    label: string
    count: number
}

/**
 * Salas del punto de venta: una sola carga por fecha (GET /bingo-card/pos/rooms)
 * y filtrado 100 % en el cliente con chips — reemplaza la cascada
 * fecha → tipo → conexión → bote de useBingoCardGenerateRoomSection.
 *
 * Los chips solo muestran los tipos que existen en las salas del día, con su
 * conteo, así el vendedor nunca elige un filtro que deja la lista vacía.
 */
export function usePosRooms(catalogs: Record<RoomFilterKey, SelectOption[]>) {
    const date = ref(localIsoDate())
    const rooms = ref<PosRoom[]>([])
    const loading = ref(false)
    const search = ref('')
    const selectedRoomId = ref<string | null>(null)

    const filters = reactive<Record<RoomFilterKey, string | null>>({
        room_type_id: null,
        connection_type_id: null,
        prize_pool_type_id: null,
    })

    /** Reloj para la cuenta regresiva de cada sala (se refresca cada 30 s). */
    const now = ref(Date.now())
    const clock = window.setInterval(() => { now.value = Date.now() }, 30_000)
    onUnmounted(() => window.clearInterval(clock))

    let fetchToken = 0

    async function fetchRooms(): Promise<void> {
        const token = ++fetchToken
        loading.value = true
        try {
            const { data } = await backendClient.get<{ rooms: PosRoom[] }>('/bingo-card/pos/rooms', {
                params: { date: date.value },
            })
            if (token !== fetchToken) return
            rooms.value = data.rooms ?? []

            // Conservar la sala elegida si sigue disponible tras recargar.
            if (selectedRoomId.value && !rooms.value.some(r => r.id === selectedRoomId.value)) {
                selectedRoomId.value = null
            }
            // Una sola sala posible (y abierta a la venta): seleccionarla directamente.
            if (!selectedRoomId.value && rooms.value.length === 1 && isSalesOpen(rooms.value[0])) {
                selectedRoomId.value = rooms.value[0].id
            }
        } catch (error: any) {
            if (token !== fetchToken) return
            rooms.value = []
            showToast(error?.response?.data?.message ?? 'No se pudieron cargar las salas.', 'error')
        } finally {
            if (token === fetchToken) loading.value = false
        }
    }

    watch(date, () => {
        selectedRoomId.value = null
        filters.room_type_id = null
        filters.connection_type_id = null
        filters.prize_pool_type_id = null
        fetchRooms()
    }, { immediate: true })

    function chipsFor(key: RoomFilterKey): FilterChip[] {
        const labels = new Map(catalogs[key].map(o => [String(o.value), o.label]))
        const counts = new Map<string, number>()
        for (const room of rooms.value) {
            const id = room[key]
            if (id) counts.set(id, (counts.get(id) ?? 0) + 1)
        }
        return [...counts.entries()].map(([value, count]) => ({
            value,
            label: labels.get(value) ?? 'Otro',
            count,
        }))
    }

    const filterGroups = computed(() => ([
        { key: 'room_type_id' as const, label: 'Tipo de sala', chips: chipsFor('room_type_id') },
        { key: 'connection_type_id' as const, label: 'Conexión', chips: chipsFor('connection_type_id') },
        { key: 'prize_pool_type_id' as const, label: 'Bote', chips: chipsFor('prize_pool_type_id') },
    ]).filter(g => g.chips.length > 1)) // un filtro con una sola opción no filtra nada

    function toggleFilter(key: RoomFilterKey, value: string): void {
        filters[key] = filters[key] === value ? null : value
    }

    const hasActiveFilters = computed(() =>
        !!(filters.room_type_id || filters.connection_type_id || filters.prize_pool_type_id || search.value.trim())
    )

    function clearFilters(): void {
        filters.room_type_id = null
        filters.connection_type_id = null
        filters.prize_pool_type_id = null
        search.value = ''
    }

    const visibleRooms = computed(() => {
        const q = search.value.trim().toLowerCase()
        return rooms.value.filter(r =>
            (!filters.room_type_id || r.room_type_id === filters.room_type_id) &&
            (!filters.connection_type_id || r.connection_type_id === filters.connection_type_id) &&
            (!filters.prize_pool_type_id || r.prize_pool_type_id === filters.prize_pool_type_id) &&
            (!q || r.uuid_code.toLowerCase().includes(q) || (r.time ?? '').startsWith(q))
        )
    })

    const selectedRoom = computed(() => rooms.value.find(r => r.id === selectedRoomId.value) ?? null)

    /**
     * Minutos hasta el cierre de ventas del vendedor (null en salas manuales).
     * Usa sales_close_at del servidor (UTC), no la fecha/hora local del
     * navegador: evita desfases si el vendedor tiene otra zona horaria.
     */
    function minutesToClose(room: PosRoom): number | null {
        if (!room.sales_close_at) return null
        return Math.floor((new Date(room.sales_close_at).getTime() - now.value) / 60_000)
    }

    /**
     * ¿Acepta ventas ahora? Se re-evalúa con el reloj: una sala puede cerrar
     * mientras el vendedor llena el formulario.
     */
    function isSalesOpen(room: PosRoom | null): boolean {
        if (!room || !room.sales_open) return false
        if (!room.sales_close_at) return true
        return new Date(room.sales_close_at).getTime() > now.value
    }

    const selectedRoomOpen = computed(() => isSalesOpen(selectedRoom.value))

    function typeLabel(key: RoomFilterKey, id: string | null): string | null {
        if (!id) return null
        return catalogs[key].find(o => String(o.value) === id)?.label ?? null
    }

    return {
        date,
        rooms,
        visibleRooms,
        loading,
        search,
        filters,
        filterGroups,
        hasActiveFilters,
        selectedRoomId,
        selectedRoom,
        selectedRoomOpen,
        toggleFilter,
        clearFilters,
        fetchRooms,
        minutesToClose,
        isSalesOpen,
        typeLabel,
    }
}
