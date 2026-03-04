import { ref, computed, reactive, watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'

import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'

import type { Country } from '@/js/types/country'
import type {
    FetchRoomResponse,
    GenerateCardPayload,
    Player,
    Room,
    RoomStep
} from '@/js/types/games/bingo/card/bingo-card-generate'

export function useBingoGenerateCard() {

    /* ───────────────── STATE ───────────────── */

    const rooms = ref<Room[]>([])
    const countries = ref<Country[]>([])
    const existsRoom = ref<RoomStep[]>([])

    const loading = ref(false)
    const loadingRooms = ref(false)
    const loadingCountries = ref(false)

    const commission = ref<number>(0)

    const filters = reactive({
        date: '',
        roomType: null as string | null,
        gameMode: null as string | null
    })

    const selectedRoom = ref<Room | null>(null)
    const selectedCountry = ref<number | null>(null)
    const numCtns = ref<number>(1)

    const player = reactive<Player>({
        phone: '',
        name: '',
        lastName: ''
    })

    let fetchToken = 0

    /* ───────────────── DERIVED ───────────────── */

    const canSelectRoomType = computed(() =>
        existsRoom.value.includes('date')
    )

    const canSelectGameMode = computed(() =>
        existsRoom.value.includes('roomType')
    )

    const canSelectRoom = computed(() =>
        existsRoom.value.includes('gameMode')
    )

    const roomCtnPrice = computed<number>(() =>
        Number(selectedRoom.value?.config?.card_price_usd ?? 0)
    )

    const totalAmount = computed<number>(() =>
        roomCtnPrice.value * numCtns.value
    )

    const sellerCommission = computed<number>(() =>
        totalAmount.value * (commission.value / 100)
    )

    const commerceAmount = computed<number>(() =>
        totalAmount.value - sellerCommission.value
    )

    const isFormValid = computed<boolean>(() => {
        if (!selectedRoom.value) return false

        return (
            !!filters.date &&
            !!filters.roomType &&
            !!filters.gameMode &&
            selectedCountry.value !== null &&
            numCtns.value >= 1 &&
            numCtns.value <= 40 &&
            player.phone.trim().length > 0 &&
            player.name.trim().length > 0 &&
            player.lastName.trim().length > 0
        )
    })

    /* ───────────────── HELPERS ───────────────── */

    function resetPlayer() {
        player.phone = ''
        player.name = ''
        player.lastName = ''
    }

    function resetRoomSelection() {
        selectedRoom.value = null
        resetFormDetails()
    }

    function resetFormDetails() {
        numCtns.value = 1
        selectedCountry.value = null
        countries.value = []
        resetPlayer()
    }

    function resetFiltersFrom(step: RoomStep) {
        if (step === 'date') {
            filters.roomType = null
            filters.gameMode = null
        }

        if (step === 'roomType') {
            filters.gameMode = null
        }

        rooms.value = []
        existsRoom.value = []
        resetRoomSelection()
    }

    function resetAfterSuccess() {
        resetFormDetails()
    }

    /* ───────────────── WATCHERS ───────────────── */

    const debouncedFetch = useDebounceFn(fetchRooms, 300)

    // 1️⃣ Cuando cambia la fecha → reset total
    watch(() => filters.date, (newValue, oldValue) => {
        if (newValue === oldValue) return

        resetFiltersFrom('date')

        if (!newValue) return

        debouncedFetch()
    })

    // 2️⃣ Cuando cambia roomType → reset gameMode
    watch(() => filters.roomType, (newValue, oldValue) => {
        if (newValue === oldValue) return

        resetFiltersFrom('roomType')

        if (!filters.date) return

        debouncedFetch()
    })

    // 3️⃣ Cuando cambia gameMode → solo recalcula rooms
    watch(() => filters.gameMode, (newValue, oldValue) => {
        if (newValue === oldValue) return

        rooms.value = []
        existsRoom.value = []
        resetRoomSelection()

        if (!filters.date) return

        debouncedFetch()
    })

    // 4️⃣ Cuando cambia la sala → cargar países
    watch(selectedRoom, async (room) => {
        resetFormDetails()

        if (!room) return

        await fetchCountries(room.id)
    })

    /* ───────────────── API ───────────────── */

    async function fetchRooms(): Promise<void> {
        if (!filters.date) {
            rooms.value = []
            existsRoom.value = []
            return
        }

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

            const steps: RoomStep[] = ['date']

            if (filters.roomType) {
                if (!response.exists) {
                    rooms.value = []
                    existsRoom.value = steps
                    return
                }
                steps.push('roomType')
            }

            if (filters.gameMode) {
                if (!response.exists) {
                    rooms.value = []
                    console.log('steps', steps)

                    existsRoom.value = steps

                    return
                }
                steps.push('gameMode')
            }

            rooms.value = response.rooms ?? []
            existsRoom.value = steps

        } catch (error: any) {
            if (currentToken !== fetchToken) return

            rooms.value = []
            existsRoom.value = []

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

    async function fetchCountries(roomId: string): Promise<void> {
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
            resetAfterSuccess()
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
        selectedRoom,
        selectedCountry,
        numCtns,
        countries,
        loading,
        loadingRooms,
        loadingCountries,
        player,
        existsRoom,

        canSelectRoomType,
        canSelectGameMode,
        canSelectRoom,

        roomCtnPrice,
        totalAmount,
        sellerCommission,
        commerceAmount,
        isFormValid,

        fetchRooms,
        generate
    }
}
