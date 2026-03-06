import { ref, computed, reactive, watch } from 'vue'
import type { Ref } from 'vue'

import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'

import type { Country } from '@/js/types/country'
import type {
    GenerateCardPayload,
    Player,
    Room
} from '@/js/types/games/bingo/card/bingo-card-generate'

export function useBingoCardGenerate(selectedRoom: Ref<Room | null>) {

    /* ───────── STATE ───────── */

    const countries = ref<Country[]>([])
    const loading = ref(false)
    const loadingCountries = ref(false)
    const commission = ref(0)

    const selectedCountry = ref<number | null>(null)
    const numCtns = ref(1)

    const player = reactive<Player>({
        phone: '',
        name: '',
        lastName: ''
    })

    /* ───────── COMPUTED ───────── */

    const roomCtnPrice = computed(() =>
        Number(selectedRoom.value?.config?.card_price_usd ?? 0)
    )

    const totalAmount = computed(() => roomCtnPrice.value * numCtns.value)

    const sellerCommission = computed(() => totalAmount.value * (commission.value / 100))

    const commerceAmount = computed(() => totalAmount.value - sellerCommission.value)

    const isFormValid = computed(() => {
        if (!selectedRoom.value) return false
        return (
            selectedCountry.value !== null &&
            numCtns.value >= 1 &&
            numCtns.value <= 40 &&
            !!player.phone.trim() &&
            !!player.name.trim() &&
            !!player.lastName.trim()
        )
    })

    /* ───────── HELPERS ───────── */

    function resetState() {
        selectedCountry.value = null
        numCtns.value = 1
        countries.value = []
        player.phone = ''
        player.name = ''
        player.lastName = ''
    }

    /* ───────── WATCHERS ───────── */

    watch(selectedRoom, async (room) => {
        resetState()
        if (room) await fetchCountries(room.id)
    })

    /* ───────── API ───────── */

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
            showToast('No se pudieron cargar los países disponibles.', 'error')
        }
        finally {
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
            const { data } = await backendClient.post('/bingo-card/generate-card-store', payload)
            showToast(data.message, 'success')
            resetState()
            return true
        }
        catch (error: any) {
            showToast(error?.response?.data?.message ?? 'Error al generar cartones.', 'error')
            return false
        }
        finally {
            loading.value = false
        }
    }

    /* ───────── RETURN ───────── */

    return {
        countries,
        selectedCountry,
        numCtns,
        player,
        loading,
        loadingCountries,
        totalAmount,
        sellerCommission,
        commerceAmount,
        isFormValid,
        generate
    }
}
