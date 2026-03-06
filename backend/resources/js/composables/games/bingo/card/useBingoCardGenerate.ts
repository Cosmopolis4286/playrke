import { ref, Ref, computed, reactive, watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'

import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'

import type { Country } from '@/js/types/country'
import type {
    FetchRoomResponse,
    GenerateCardPayload,
    Player,
    Room,
    RoomStepStatus
} from '@/js/types/games/bingo/card/bingo-card-generate'

/**
* API pública del composable useBingoGenerateCard
* Mejora el autocompletado cuando se usa en componentes.
*/
export interface UseBingoGenerateCard {
    filters: {
        date: string
        roomType: string | null
        gameMode: string | null
    }

    rooms: Ref<Room[]>
    countries: Ref<Country[]>

    selectedRoom: Ref<Room | null>
    selectedCountry: Ref<number | null>

    numCtns: Ref<number>

    player: Player

    loading: Ref<boolean>
    loadingRooms: Ref<boolean>
    loadingCountries: Ref<boolean>

    roomStepStatus: Ref<string>

    roomCtnPrice: Ref<number>
    totalAmount: Ref<number>
    sellerCommission: Ref<number>
    commerceAmount: Ref<number>

    isFormValid: Ref<boolean>

    generate: () => Promise<boolean>
}

/**
 * Composable encargado de la lógica para generar cartones de bingo.
 *
 * Responsabilidades:
 * - Manejar filtros de salas
 * - Obtener salas disponibles
 * - Obtener países permitidos por sala
 * - Manejar datos del jugador
 * - Calcular montos y comisiones
 * - Generar los cartones
 */
export function useBingoGenerateCard() {

    /* ───────── STATE ───────── */

    /** Lista de salas disponibles según filtros */
    const rooms = ref<Room[]>([])

    /** Países disponibles para la sala seleccionada */
    const countries = ref<Country[]>([])

    /** Loading general del proceso de generación */
    const loading = ref(false)

    /** Loading al buscar salas */
    const loadingRooms = ref(false)

    /** Loading al buscar países */
    const loadingCountries = ref(false)

    /** Estado del flujo de selección de sala */
    const roomStepStatus = ref<RoomStepStatus>('idle')

    /** Porcentaje de comisión del vendedor */
    const commission = ref(0)

    /**
     * Filtros utilizados para buscar salas.
     */
    const filters = reactive({
        date: '',
        roomType: null as string | null,
        gameMode: null as string | null
    })

    /** Sala seleccionada */
    const selectedRoom = ref<Room | null>(null)

    /** País seleccionado */
    const selectedCountry = ref<number | null>(null)

    /** Cantidad de cartones a generar */
    const numCtns = ref(1)

    /**
     * Datos del jugador que compra los cartones
     */
    const player = reactive<Player>({
        phone: '',
        name: '',
        lastName: ''
    })

    /**
     * Token utilizado para evitar race conditions
     * cuando hay múltiples requests de salas.
     */
    let fetchToken = 0

    /* ───────── COMPUTED ───────── */

    /**
     * Permite seleccionar el tipo de sala
     */
    const canSelectRoomType = computed(() =>
        !!filters.date && roomStepStatus.value === 'ready'
    )

    /**
     * Permite seleccionar el modo de juego
     */
    const canSelectGameMode = computed(() =>
        !!filters.roomType && roomStepStatus.value === 'ready'
    )

    /**
     * Permite seleccionar una sala
     */
    const canSelectRoom = computed(() =>
        !!filters.gameMode && rooms.value.length > 0
    )

    /**
     * Mensaje de alerta según estado del flujo de salas
     */
    const roomAlert = computed(() => {

        switch (roomStepStatus.value) {

            case 'noDateRooms':
                return 'No hay salas disponibles para la fecha seleccionada.'

            case 'noRoomTypeRooms':
                return 'No hay salas disponibles para el tipo de sala.'

            case 'noGameModeRooms':
                return 'No hay salas disponibles para el modo de juego.'

            default:
                return null

        }

    })

    /**
     * Precio unitario del cartón
     */
    const roomCtnPrice = computed(() =>
        Number(selectedRoom.value?.config?.card_price_usd ?? 0)
    )

    /**
     * Total a pagar por los cartones
     */
    const totalAmount = computed(() =>
        roomCtnPrice.value * numCtns.value
    )

    /**
     * Comisión correspondiente al vendedor
     */
    const sellerCommission = computed(() =>
        totalAmount.value * (commission.value / 100)
    )

    /**
     * Monto que recibe el comercio
     */
    const commerceAmount = computed(() =>
        totalAmount.value - sellerCommission.value
    )

    /**
     * Valida si el formulario está listo para generar cartones
     */
    const isFormValid = computed(() => {

        if (!selectedRoom.value) return false

        return (
            !!filters.date &&
            !!filters.roomType &&
            !!filters.gameMode &&
            selectedCountry.value !== null &&
            numCtns.value >= 1 &&
            numCtns.value <= 40 &&
            player.phone.trim() &&
            player.name.trim() &&
            player.lastName.trim()
        )

    })

    /* ───────── HELPERS ───────── */

    /**
     * Limpia los datos del jugador
     */
    function resetPlayer() {

        player.phone = ''
        player.name = ''
        player.lastName = ''

    }

    /**
     * Reinicia estado dependiente de la sala seleccionada
     */
    function resetRoomState() {

        selectedRoom.value = null
        selectedCountry.value = null
        countries.value = []
        numCtns.value = 1

        resetPlayer()

    }

    /**
     * Limpia lista de salas y estado del flujo
     */
    function resetRooms() {

        rooms.value = []
        roomStepStatus.value = 'idle'

        resetRoomState()

    }

    /* ───────── WATCHERS ───────── */

    /**
     * Versión debounced de fetchRooms para evitar
     * múltiples requests mientras el usuario cambia filtros.
     */
    const debouncedFetchRooms = useDebounceFn(fetchRooms, 300)

    /**
     * Observa cambios en filtros para recargar salas
     */
    watch(
        () => [filters.date, filters.roomType, filters.gameMode],
        () => {

            resetRooms()

            if (!filters.date) return

            debouncedFetchRooms()

        }
    )

    /**
     * Cuando cambia la sala se cargan los países disponibles
     */
    watch(selectedRoom, async (room) => {

        selectedCountry.value = null
        countries.value = []
        numCtns.value = 1

        resetPlayer()

        if (!room) return

        await fetchCountries(room.id)

    })

    /* ───────── API ───────── */

    /**
     * Obtiene salas disponibles según filtros.
     *
     * Implementa control de concurrencia para evitar
     * que respuestas antiguas sobrescriban el estado.
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

                if (!filters.roomType) {
                    roomStepStatus.value = 'noDateRooms'
                }
                else if (!filters.gameMode) {
                    roomStepStatus.value = 'noRoomTypeRooms'
                }
                else {
                    roomStepStatus.value = 'noGameModeRooms'
                }

                return

            }

            roomStepStatus.value = 'ready'

        }
        catch (error: any) {

            if (currentToken !== fetchToken) return

            rooms.value = []
            roomStepStatus.value = 'idle'

            showToast(
                error?.response?.data?.message ??
                'No se pudieron cargar las salas.',
                'error'
            )

        }
        finally {

            if (currentToken === fetchToken) {
                loadingRooms.value = false
            }

        }

    }

    /**
     * Obtiene los países disponibles para una sala.
     *
     * @param roomId ID de la sala
     */
    async function fetchCountries(roomId: string) {

        loadingCountries.value = true

        try {

            const { data } = await backendClient.get<{ countries: Country[] }>(
                `/bingo-card/${roomId}/available-countries`
            )

            countries.value = data.countries ?? []

        }
        catch {

            countries.value = []

            showToast(
                'No se pudieron cargar los países disponibles.',
                'error'
            )

        }
        finally {

            loadingCountries.value = false

        }

    }

    /**
     * Genera los cartones de bingo.
     *
     * @returns true si la operación fue exitosa
     */
    async function generate(): Promise<boolean> {

        if (!isFormValid.value || loading.value) return false

        loading.value = true

        const payload: GenerateCardPayload = {
            room_id: selectedRoom.value!.id,
            num_ctns: numCtns.value,
            country_id: selectedCountry.value,
            player: {
                phone: player.phone.trim(),
                name: player.name.trim(),
                last_name: player.lastName.trim()
            }
        }

        try {

            const { data } = await backendClient.post(
                '/bingo-card/generate-card-store',
                payload
            )

            showToast(data.message, 'success')

            resetRoomState()

            return true

        }
        catch (error: any) {

            showToast(
                error?.response?.data?.message ??
                'Error al generar cartones.',
                'error'
            )

            return false

        }
        finally {

            loading.value = false

        }

    }

    /* ───────── RETURN ───────── */

    return {

        filters,
        rooms,
        countries,

        selectedRoom,
        selectedCountry,
        numCtns,
        player,

        loading,
        loadingRooms,
        loadingCountries,

        roomStepStatus,
        roomAlert,

        canSelectRoomType,
        canSelectGameMode,
        canSelectRoom,

        roomCtnPrice,
        totalAmount,
        sellerCommission,
        commerceAmount,

        isFormValid,

        generate
    }
}
