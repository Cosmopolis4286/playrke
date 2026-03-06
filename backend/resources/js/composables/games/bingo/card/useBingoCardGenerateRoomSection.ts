import { ref, computed, reactive, watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'

import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'

import type {
    FetchRoomResponse,
    Room,
    RoomStepStatus
} from '@/js/types/games/bingo/card/bingo-card-generate'

export function useBingoCardGenerateRoomSection() {

    /* ───────── STATE ───────── */

    const rooms = ref<Room[]>([])
    const loadingRooms = ref(false)
    const roomStepStatus = ref<RoomStepStatus>('idle')

    const filters = reactive({
        date: '',
        roomType: null as string | null,
        gameMode: null as string | null
    })

    const selectedRoom = ref<Room | null>(null)

    let fetchToken = 0

    /* ───────── COMPUTED ───────── */

    const canSelectRoomType = computed(() =>
        !!filters.date && roomStepStatus.value === 'ready'
    )

    const canSelectGameMode = computed(() =>
        !!filters.roomType && roomStepStatus.value === 'ready'
    )

    const canSelectRoom = computed(() =>
        !!filters.gameMode && rooms.value.length > 0
    )

    const roomAlert = computed(() => {
        switch (roomStepStatus.value) {
            case 'noDateRooms':    return 'No hay salas disponibles para la fecha seleccionada.'
            case 'noRoomTypeRooms': return 'No hay salas disponibles para el tipo de sala.'
            case 'noGameModeRooms': return 'No hay salas disponibles para el modo de juego.'
            default: return null
        }
    })

    /* ───────── HELPERS ───────── */

    function resetRooms() {
        rooms.value = []
        roomStepStatus.value = 'idle'
        selectedRoom.value = null
    }

    /* ───────── WATCHERS ───────── */

    const debouncedFetchRooms = useDebounceFn(fetchRooms, 300)

    watch(
        () => [filters.date, filters.roomType, filters.gameMode],
        () => {
            resetRooms()
            if (!filters.date) return
            debouncedFetchRooms()
        }
    )

    /* ───────── API ───────── */

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
                if (!filters.roomType)       roomStepStatus.value = 'noDateRooms'
                else if (!filters.gameMode)  roomStepStatus.value = 'noRoomTypeRooms'
                else                         roomStepStatus.value = 'noGameModeRooms'
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
        loadingRooms,
        roomAlert,
        canSelectRoomType,
        canSelectGameMode,
        canSelectRoom
    }
}
