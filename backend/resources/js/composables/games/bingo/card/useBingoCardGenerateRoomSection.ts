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
        gameMode: null as string | null,
        prizePoolType: null as string | null,
    })

    /**
     * Indica si el servidor confirmó que existe al menos una sala para el
     * roomType+gameMode actual (response.exists === true con gameMode activo).
     * Se resetea en cuanto cambia gameMode o roomType.
     */
    const gameModeConfirmed = ref(false)

    /** Sala seleccionada por el usuario. Expuesta vía `defineExpose` al padre. */
    const selectedRoom = ref<Room | null>(null)

    /** ID de la sala seleccionada (para FormSelect). */
    const selectedRoomId = computed({
        get: () => selectedRoom.value?.id ?? null,
        set: (id: string | null) => {
            selectedRoom.value = rooms.value.find(r => r.id === id) ?? null
        }
    })

    /** Opciones de sala con label compuesto: "HH:MM — UUID — N jugadores — $X bote" */
    const roomOptions = computed(() =>
        rooms.value.map(room => ({
            id:    room.id,
            label: [
                room.config?.time?.slice(0, 5) ?? '--:--',
                room.uuid_code,
                `${room.total_players} jugadores`,
                `$${Number(room.prize_pool).toFixed(2)} bote`,
            ].join('  —  '),
        }))
    )

    /**
     * Token incremental para evitar race conditions.
     * Solo se aplica el resultado del fetch más reciente.
     */
    let fetchToken = 0

    /* ───────── COMPUTED ───────── */

    /** El fetch está en curso. */
    const isLoading = computed(() => roomStepStatus.value === 'loading')

    /**
     * Visibilidad: fecha elegida y el primer fetch terminó con al menos un resultado.
     * Se mantiene visible durante cualquier carga posterior (fecha ya validada).
     */
    const showRoomType = computed(() =>
        !!filters.date &&
        (roomStepStatus.value === 'ready' ||
         roomStepStatus.value === 'noRoomTypeRooms' ||
         roomStepStatus.value === 'noGameModeRooms' ||
         roomStepStatus.value === 'noPrizePoolTypeRooms' ||
         roomStepStatus.value === 'loading')
    )

    /**
     * Visibilidad: roomType elegido y el fetch retornó exists para roomType.
     * Se mantiene visible durante cualquier carga posterior (roomType ya validado).
     */
    const showGameMode = computed(() =>
        !!filters.roomType &&
        (roomStepStatus.value === 'ready' ||
         roomStepStatus.value === 'noGameModeRooms' ||
         roomStepStatus.value === 'noPrizePoolTypeRooms' ||
         roomStepStatus.value === 'loading')
    )

    /**
     * Visibilidad: el servidor confirmó salas para el gameMode actual.
     * No se muestra durante la carga del gameMode — solo tras respuesta positiva.
     * Se mantiene visible en cargas posteriores (prizePoolType ya validado).
     */
    const showPrizePoolType = computed(() =>
        gameModeConfirmed.value &&
        (roomStepStatus.value === 'ready' ||
         roomStepStatus.value === 'noPrizePoolTypeRooms' ||
         roomStepStatus.value === 'loading')
    )

    /**
     * Habilita el selector de sala.
     * Requiere gameMode seleccionado y al menos una sala disponible.
     * prizePoolType es opcional y solo filtra las salas mostradas.
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
            case 'noDateRooms':          return 'No hay salas disponibles para la fecha seleccionada.'
            case 'noRoomTypeRooms':      return 'No hay salas disponibles para el tipo de sala.'
            case 'noGameModeRooms':      return 'No hay salas disponibles para el modo de juego.'
            case 'noPrizePoolTypeRooms': return 'No hay salas disponibles para el tipo de bote.'
            default:                     return ''
        }
    })

    /* ───────── HELPERS ───────── */

    /* ───────── WATCHERS ───────── */

    /** Versión debounced de `fetchRooms` para evitar peticiones en cada keystroke. */
    const debouncedFetchRooms = useDebounceFn(fetchRooms, 300)

    /**
     * Al cambiar la fecha: resetea todo y relanza el fetch.
     */
    watch(
        () => filters.date,
        () => {
            filters.roomType = null
            filters.gameMode = null
            rooms.value = []
            roomStepStatus.value = 'idle'
            selectedRoom.value = null
            if (!filters.date) return
            debouncedFetchRooms()
        }
    )

    /**
     * Al cambiar el tipo de sala: limpia gameMode, prizePoolType y selectedRoom.
     * Si se limpia roomType pero hay fecha, relanza el fetch sin roomType para
     * mantener el campo visible (ya se hizo consulta de fecha).
     */
    watch(
        () => filters.roomType,
        () => {
            filters.gameMode = null
            filters.prizePoolType = null
            gameModeConfirmed.value = false
            selectedRoom.value = null
            if (!filters.date) {
                rooms.value = []
                roomStepStatus.value = 'idle'
                return
            }
            roomStepStatus.value = 'loading'
            rooms.value = []
            debouncedFetchRooms()
        }
    )

    /**
     * Al cambiar el modo de juego: limpia prizePoolType y selectedRoom.
     * Adelanta status a 'loading' antes de limpiar rooms para evitar parpadeo.
     */
    watch(
        () => filters.gameMode,
        () => {
            filters.prizePoolType = null
            gameModeConfirmed.value = false
            selectedRoom.value = null
            if (!filters.date || !filters.gameMode) {
                rooms.value = []
                return
            }
            roomStepStatus.value = 'loading'
            rooms.value = []
            debouncedFetchRooms()
        }
    )

    /**
     * Al cambiar el tipo de bote: limpia la sala y relanza el fetch si hay gameMode.
     * Adelanta status a 'loading' antes de limpiar rooms para evitar parpadeo.
     */
    watch(
        () => filters.prizePoolType,
        () => {
            selectedRoom.value = null
            if (!filters.date || !filters.gameMode) {
                rooms.value = []
                return
            }
            roomStepStatus.value = 'loading'
            rooms.value = []
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
                        date:          filters.date,
                        roomType:      filters.roomType,
                        gameMode:      filters.gameMode,
                        prizePoolType: filters.prizePoolType,
                    }
                }
            )

            if (currentToken !== fetchToken) return

            const response = data.data

            rooms.value = response.rooms ?? []

            if (!response.exists) {
                if (!filters.roomType)          roomStepStatus.value = 'noDateRooms'
                else if (!filters.gameMode)     roomStepStatus.value = 'noRoomTypeRooms'
                else if (filters.prizePoolType) roomStepStatus.value = 'noPrizePoolTypeRooms'
                else                            roomStepStatus.value = 'noGameModeRooms'
                gameModeConfirmed.value = false
                return
            }

            roomStepStatus.value = 'ready'
            if (filters.gameMode) gameModeConfirmed.value = true

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
        roomOptions,
        selectedRoom,
        selectedRoomId,
        loadingRooms,
        isLoading,
        roomAlert,
        showRoomType,
        showGameMode,
        showPrizePoolType,
        canSelectRoom,
    }
}
