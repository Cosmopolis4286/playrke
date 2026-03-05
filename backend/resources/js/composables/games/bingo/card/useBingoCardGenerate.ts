import { ref, computed, reactive, watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'

import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'

import type { Country } from '@/js/types/country'
import type {
    FetchRoomResponse,
    GenerateCardPayload,
    Player,
    Room
} from '@/js/types/games/bingo/card/bingo-card-generate'

export function useBingoGenerateCard() {

    /* ───────────────── STATE ───────────────── */

    const rooms = ref<Room[]>([])
    const countries = ref<Country[]>([])

    const loading = ref(false)
    const loadingRooms = ref(false)
    const loadingCountries = ref(false)

    const existsRoom = ref<boolean | null>(null)
    const commission = ref<number>(0)

    const filters = reactive({
        date: '',
        roomType: null as string | null,
        gameMode: null as string | null
    })

    const selectedRoom = ref<Room | null>(null)
    const selectedCountry = ref<number | null>(null)
    const numCtns = ref(1)

    const player = reactive<Player>({
        phone: '',
        name: '',
        lastName: ''
    })

    let fetchToken = 0

    /* ───────────────── COMPUTED ───────────────── */

    const canSelectRoomType = computed(() =>
        !!filters.date && existsRoom.value === true
    )

    const canSelectGameMode = computed(() =>
        canSelectRoomType.value && !!filters.roomType
    )

    const canSelectRoom = computed(() =>
        canSelectGameMode.value && !!filters.gameMode
    )

    const noRoomsForDate = computed(() =>
        !!filters.date &&
        existsRoom.value === false &&
        !loadingRooms.value
    )

    const roomCtnPrice = computed(() =>
        Number(selectedRoom.value?.config?.card_price_usd ?? 0)
    )

    const totalAmount = computed(() =>
        roomCtnPrice.value * numCtns.value
    )

    const sellerCommission = computed(() =>
        totalAmount.value * (commission.value / 100)
    )

    const commerceAmount = computed(() =>
        totalAmount.value - sellerCommission.value
    )

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

    /* ───────────────── HELPERS ───────────────── */

    function resetPlayer() {
        player.phone = ''
        player.name = ''
        player.lastName = ''
    }

    function resetRoomState() {
        selectedRoom.value = null
        selectedCountry.value = null
        countries.value = []
        numCtns.value = 1
        resetPlayer()
    }

    function resetRooms() {
        rooms.value = []
        existsRoom.value = null
        resetRoomState()
    }

    /* ───────────────── WATCHERS ───────────────── */

    const debouncedFetchRooms = useDebounceFn(fetchRooms, 300)

    watch(
        () => [filters.date, filters.roomType, filters.gameMode],
        () => {
            resetRooms()

            if (!filters.date) return
            debouncedFetchRooms()
        }
    )

    watch(selectedRoom, async (room) => {
        selectedCountry.value = null
        countries.value = []
        numCtns.value = 1
        resetPlayer()

        if (!room) return
        await fetchCountries(room.id)
    })

    /* ───────────────── API ───────────────── */

    async function fetchRooms() {
        const currentToken = ++fetchToken
        loadingRooms.value = true

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

            existsRoom.value = response.exists
            rooms.value = response.rooms ?? []

        } catch (error: any) {
            if (currentToken !== fetchToken) return

            existsRoom.value = false
            rooms.value = []

            showToast(
                error?.response?.data?.message ??
                'No se pudieron cargar las salas.',
                'error'
            )

        } finally {
            if (currentToken === fetchToken) {
                loadingRooms.value = false
            }
        }
    }

    async function fetchCountries(roomId: string) {
        loadingCountries.value = true

        try {
            const { data } = await backendClient.get<{ countries: Country[] }>(
                `/bingo-card/${roomId}/available-countries`
            )

            countries.value = data.countries ?? []

        } catch {
            countries.value = []

            showToast(
                'No se pudieron cargar los países disponibles.',
                'error'
            )

        } finally {
            loadingCountries.value = false
        }
    }

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

        } catch (error: any) {
            showToast(
                error?.response?.data?.message ??
                'Error al generar cartones.',
                'error'
            )

            return false

        } finally {
            loading.value = false
        }
    }

    /* ───────────────── RETURN ───────────────── */

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

        existsRoom,
        canSelectRoomType,
        canSelectGameMode,
        canSelectRoom,
        noRoomsForDate,

        roomCtnPrice,
        totalAmount,
        sellerCommission,
        commerceAmount,
        isFormValid,

        generate
    }
}
