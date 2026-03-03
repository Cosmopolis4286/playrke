import { ref, computed, reactive, watch } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'

import type { Country } from '@/js/types/country'
import type {
    generateCardPayload,
    Player,
    Room
} from '@/js/types/games/bingo/card/bingo-card-generate'
import type { SelectOption } from '@/js/types/common/form/form'

export function useBingoGenerateCard() {
    /* ───────────────────────────────────────────
     * Estado fuente
     * ─────────────────────────────────────────── */

    const rooms = ref<Room[]>([])
    const commission = ref<number>(0)

    function setRooms(data: Room[]) {
        rooms.value = data ?? []
    }

    function setCommission(value: number) {
        commission.value = value ?? 0
    }

    /* ───────────────────────────────────────────
     * Filtros
     * ─────────────────────────────────────────── */

    const filters = reactive({
        date: '',
        roomType: null as string | null,
        gameMode: null as string | null,
    })

    const selectedRoom = ref<Room | null>(null)
    const numCtns = ref(1)
    const selectedCountry = ref<number | null>(null)

    const loading = ref(false)
    const loadingCountries = ref(false)
    const countries = ref<Country[]>([])

    const player = reactive<Player>({
        phone: '',
        name: '',
        lastName: '',
    })

    /* ───────────────────────────────────────────
     * Computeds
     * ─────────────────────────────────────────── */

    const roomTypeOptions = computed<SelectOption[]>(() => {
        const types = [...new Set(rooms.value.map(r => r.room_type))]
        return types.map(type => ({ value: type, label: type }))
    })

    const gameModeOptions = computed<SelectOption[]>(() => {
        if (!filters.roomType) return []

        const modes = [
            ...new Set(
                rooms.value
                    .filter(r => r.room_type === filters.roomType)
                    .map(r => r.game_mode)
            )
        ]

        return modes.map(mode => ({ value: mode, label: mode }))
    })

    const filteredRooms = computed(() =>
        rooms.value.filter(r =>
            (!filters.date || r.date === filters.date) &&
            (!filters.roomType || r.room_type === filters.roomType) &&
            (!filters.gameMode || r.game_mode === filters.gameMode)
        )
    )

    const roomCtnPrice = computed(() =>
        Number(selectedRoom.value?.config?.card_price_usd ?? 0)
    )

    const totalAmount = computed(() =>
        roomCtnPrice.value * numCtns.value
    )

    const sellerCommission = computed(() =>
        (totalAmount.value * commission.value) / 100
    )

    const commerceAmount = computed(() =>
        totalAmount.value - sellerCommission.value
    )

    const isFormValid = computed(() =>
        !!filters.date &&
        !!filters.roomType &&
        !!filters.gameMode &&
        !!selectedRoom.value &&
        numCtns.value >= 1 &&
        numCtns.value <= 40 &&
        !!selectedCountry.value &&
        !!player.phone.trim() &&
        !!player.name.trim() &&
        !!player.lastName.trim()
    )

    /* ───────────────────────────────────────────
     * Watchers
     * ─────────────────────────────────────────── */

    watch(
        () => filters.date,
        () => {
            filters.roomType = null
            filters.gameMode = null
            selectedRoom.value = null
        }
    )

    watch(
        () => filters.roomType,
        () => {
            filters.gameMode = null
            selectedRoom.value = null
        }
    )

    watch(
        () => filters.gameMode,
        () => {
            selectedRoom.value = null
        }
    )

    watch(selectedRoom, async room => {
        resetFormDetails()

        if (room) {
            await fetchCountries(room.id)
        }
    })

    /* ───────────────────────────────────────────
     * Backend
     * ─────────────────────────────────────────── */

    async function fetchCountries(roomId: string) {
        loadingCountries.value = true

        try {
            const { data } = await backendClient.get<{
                countries: Country[]
            }>(`/bingo-card/${roomId}/available-countries`)

            countries.value = data.countries ?? []
        } catch {
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

        const payload: generateCardPayload = {
            room_id: selectedRoom.value!.id,
            num_ctns: numCtns.value,
            country_id: selectedCountry.value,
            player: {
                phone: player.phone.trim(),
                name: player.name.trim(),
                last_name: player.lastName.trim(),
            },
        }

        try {
            const { data } = await backendClient.post(
                '/bingo-card/generate-card-store',
                payload
            )

            showToast(data.message, 'success')
            resetFormDetails()
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

    /* ───────────────────────────────────────────
     * Helpers
     * ─────────────────────────────────────────── */

    function resetPlayer() {
        player.phone = ''
        player.name = ''
        player.lastName = ''
    }

    function resetFormDetails() {
        numCtns.value = 1
        selectedCountry.value = null
        countries.value = []
        resetPlayer()
    }

    /* ─────────────────────────────────────────── */

    return {
        // setters
        setRooms,
        setCommission,

        // state
        filters,
        selectedRoom,
        numCtns,
        selectedCountry,
        countries,
        loadingCountries,
        player,
        loading,

        // computed
        roomTypeOptions,
        gameModeOptions,
        filteredRooms,
        roomCtnPrice,
        totalAmount,
        sellerCommission,
        commerceAmount,
        isFormValid,

        // actions
        generate,
    }
}
