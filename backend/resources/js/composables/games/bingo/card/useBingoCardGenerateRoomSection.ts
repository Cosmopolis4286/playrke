import { ref, computed, reactive, watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'

import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'

import type {
    FetchRoomResponse,
    Room,
    RoomStepStatus
} from '@/js/types/games/bingo/card/bingo-card-generate'

/**
 * Composable autónomo para la sección de selección de sala en el formulario de generación de cartones.
 *
 * Responsabilidades:
 * - Gestionar los filtros de búsqueda (fecha, tipo de sala, modo de juego).
 * - Obtener salas disponibles desde la API con debounce y control de concurrencia.
 * - Calcular la visibilidad progresiva de los selectores (fecha → tipo → modo → sala).
 * - Exponer `selectedRoom` para que el componente padre lo consuma vía `defineExpose`.
 *
 * @returns Estado y métodos necesarios para `BingoCardGenerateFormRoomSection`.
 */
export function useBingoCardGenerateRoomSection() {

    /* ───────── STATE ───────── */

    /** Salas disponibles según los filtros activos. */
    const rooms = ref<Room[]>([])

    /** Indica si se está realizando una petición de salas. */
    const loadingRooms = ref(false)

    /**
     * Estado interno del flujo de selección de sala.
     * Controla alertas y visibilidad de campos.
     */
    const roomStepStatus = ref<RoomStepStatus>('idle')

    /**
     * Filtros reactivos de búsqueda.
     * Cualquier cambio dispara un nuevo fetch de salas (con debounce).
     */
    const filters = reactive({
        date: '',
        roomType: null as string | null,
        gameMode: null as string | null
    })

    /** Sala seleccionada por el usuario. Expuesta vía `defineExpose` al padre. */
    const selectedRoom = ref<Room | null>(null)

    /** ID de la sala seleccionada (para FormSelect). */
    const selectedRoomId = computed({
        get: () => selectedRoom.value?.id ?? null,
        set: (id: string | null) => {
            selectedRoom.value = rooms.value.find(r => r.id === id) ?? null
        }
    })

    /**
     * Token incremental para evitar race conditions.
     * Solo se aplica el resultado del fetch más reciente.
     */
    let fetchToken = 0

    /* ───────── COMPUTED ───────── */

    /**
     * Habilita el selector de tipo de sala.
     * Requiere fecha seleccionada y que las salas hayan cargado correctamente.
     */
    const canSelectRoomType = computed(() =>
        !!filters.date && roomStepStatus.value === 'ready'
    )

    /**
     * Habilita el selector de modo de juego.
     * Requiere tipo de sala seleccionado y estado listo.
     */
    const canSelectGameMode = computed(() =>
        !!filters.roomType && roomStepStatus.value === 'ready'
    )

    /**
     * Habilita el selector de sala.
     * Requiere modo de juego seleccionado y al menos una sala disponible.
     */
    const canSelectRoom = computed(() =>
        !!filters.gameMode && rooms.value.length > 0
    )

    /**
     * Mensaje de alerta descriptivo según el estado del flujo.
     * Retorna `null` cuando no hay alerta que mostrar.
     */
    const roomAlert = computed(() => {
        switch (roomStepStatus.value) {
            case 'noDateRooms':     return 'No hay salas disponibles para la fecha seleccionada.'
            case 'noRoomTypeRooms': return 'No hay salas disponibles para el tipo de sala.'
            case 'noGameModeRooms': return 'No hay salas disponibles para el modo de juego.'
            default:                return null
        }
    })

    /* ───────── HELPERS ───────── */

    /**
     * Resetea la lista de salas, el estado del flujo y la sala seleccionada.
     * Se llama al cambiar cualquier filtro.
     */
    function resetRooms() {
        rooms.value = []
        roomStepStatus.value = 'idle'
        selectedRoom.value = null
    }

    /* ───────── WATCHERS ───────── */

    /** Versión debounced de `fetchRooms` para evitar peticiones en cada keystroke. */
    const debouncedFetchRooms = useDebounceFn(fetchRooms, 300)

    /**
     * Observa los tres filtros en conjunto.
     * Al cambiar cualquiera: resetea el estado y lanza un nuevo fetch si hay fecha.
     */
    watch(
        () => [filters.date, filters.roomType, filters.gameMode],
        () => {
            resetRooms()
            if (!filters.date) return
            debouncedFetchRooms()
        }
    )

    /* ───────── API ───────── */

    /**
     * Obtiene las salas disponibles según los filtros activos.
     *
     * Implementa control de concurrencia mediante token incremental:
     * si llega una respuesta obsoleta (token distinto), se descarta.
     */
    async function fetchRooms() {

        const currentToken = ++fetchToken

        loadingRooms.value = true
        roomStepStatus.value = 'loading'

        try {

            const { data } = await backendClient.get<FetchRoomResponse>(
                '/bingo-card/fetch-room',
                {
                    params: {
                        date: filters.date,
                        roomType: filters.roomType,
                        gameMode: filters.gameMode
                    }
                }
            )

            if (currentToken !== fetchToken) return

            const response = data.data

            rooms.value = response.rooms ?? []

            if (!response.exists) {
                if (!filters.roomType)      roomStepStatus.value = 'noDateRooms'
                else if (!filters.gameMode) roomStepStatus.value = 'noRoomTypeRooms'
                else                        roomStepStatus.value = 'noGameModeRooms'
                return
            }

            roomStepStatus.value = 'ready'

        }
        catch (error: any) {

            if (currentToken !== fetchToken) return

            rooms.value = []
            roomStepStatus.value = 'idle'

            showToast(
                error?.response?.data?.message ?? 'No se pudieron cargar las salas.',
                'error'
            )

        }
        finally {

            if (currentToken === fetchToken) {
                loadingRooms.value = false
            }

        }

    }

    /* ───────── RETURN ───────── */

    return {
        filters,
        rooms,
        selectedRoom,
        selectedRoomId,
        loadingRooms,
        roomAlert,
        canSelectRoomType,
        canSelectGameMode,
        canSelectRoom
    }
}
