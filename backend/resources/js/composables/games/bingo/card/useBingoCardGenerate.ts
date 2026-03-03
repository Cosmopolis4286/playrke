import { ref, computed, reactive, watch } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'
import type { Country } from '@/js/types/country'
import type { generateCardPayload, Player, Room } from '@/js/types/games/bingo/card/bingo-card-generate'


/**
 * Composable para la generación de cartones de Bingo (rol seller).
 *
 * Responsabilidades:
 * - Estado del formulario de venta
 * - Carga de países disponibles por sala
 * - Cálculos de montos (total, comisión, monto banca)
 * - Llamada al endpoint POST /bingo-card/generate-card
 */
export function useBingogenerateCard(commission: number) {
    // ─── Estado ────────────────────────────────────────────────────────────────

    const selectedRoom = ref<Room | null>(null)
    const numCtns = ref<number>(1)
    const selectedCountry = ref<number | null>(null)
    const loading = ref(false)
    const countries = ref<Country[]>([])
    const loadingCountries = ref(false)

    const player = reactive<Player>({
        phone: '',
        name: '',
        lastName: '',
    })

    // ─── Computed ──────────────────────────────────────────────────────────────

    const roomCtnPrice = computed<number>(() =>
        Number(selectedRoom.value?.config?.card_price_usd ?? 0)
    )

    const totalAmount = computed<number>(() =>
        roomCtnPrice.value * numCtns.value
    )

    const sellerCommission = computed<number>(() =>
        (totalAmount.value * commission) / 100
    )

    const commerceAmount = computed<number>(() =>
        totalAmount.value - sellerCommission.value
    )

    const isFormValid = computed<boolean>(() => {
        return (
            !!selectedRoom.value &&
            numCtns.value >= 1 &&
            numCtns.value <= 40 &&
            !!selectedCountry.value &&
            !!player.phone.trim() &&
            !!player.name.trim() &&
            !!player.lastName.trim()
        )
    })

    // ─── Watchers ──────────────────────────────────────────────────────────────

    watch(selectedRoom, async (room) => {
        numCtns.value = 1
        selectedCountry.value = null
        countries.value = []
        player.phone = ''
        player.name = ''
        player.lastName = ''

        if (room) {
            await fetchCountries(room.id)
        }
    })

    // ─── Métodos ───────────────────────────────────────────────────────────────

    /**
     * Carga los países disponibles para la sala seleccionada
     * (usa el endpoint existente GET /bingo-card/{roomId}/available-countries)
     */
    async function fetchCountries(roomId: string): Promise<void> {
        loadingCountries.value = true
        try {
            const response = await backendClient.get<{ countries: Country[] }>(
                `/bingo-card/${roomId}/available-countries`
            )
            countries.value = response.data.countries ?? []
        } catch {
            showToast('No se pudieron cargar los países disponibles.', 'error')
            countries.value = []
        } finally {
            loadingCountries.value = false
        }
    }

    /**
     * Genera los cartones enviando el formulario al backend.
     * Retorna `true` en éxito, `false` en error.
     */
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
            const response = await backendClient.post<{
                type: string
                message: string
                playerLink?: string
            }>('/bingo-card/generate-card-store', payload)

            const data = response.data

            if (data.type === 'success') {
                showToast(data.message, 'success')
                resetPlayer()
                numCtns.value = 1
                return true
            }

            showToast(data.message ?? 'Error al generar cartones.', 'warning')
            return false
        } catch (error: any) {
            const msg =
                error?.response?.data?.message ??
                'Error al generar cartones.'
            showToast(msg, 'error')
            return false
        } finally {
            loading.value = false
        }
    }

    function resetPlayer(): void {
        player.phone = ''
        player.name = ''
        player.lastName = ''
    }

    // ─── Return ────────────────────────────────────────────────────────────────

    return {
        selectedRoom,
        numCtns,
        selectedCountry,
        countries,
        loadingCountries,
        player,
        loading,
        roomCtnPrice,
        totalAmount,
        sellerCommission,
        commerceAmount,
        isFormValid,
        generate,
    }
}
