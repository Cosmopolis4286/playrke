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

/**
 * Composable encargado de la lógica de generación de cartones de bingo.
 *
 * Responsabilidades:
 * - Obtener los países disponibles al seleccionar una sala.
 * - Gestionar los datos del jugador, cantidad de cartones y país.
 * - Calcular montos totales, comisión del vendedor y monto al comercio.
 * - Validar el formulario antes de enviar.
 * - Ejecutar la petición de generación de cartones.
 *
 * @param selectedRoom - Ref reactiva a la sala seleccionada, provista por `useBingoCardGenerateRoomSection` vía el componente padre.
 * @returns Estado y métodos necesarios para `BingoCardGenerateFormPlayerSection` y el formulario principal.
 */
export function useBingoCardGenerate(selectedRoom: Ref<Room | null>) {

    /* ───────── STATE ───────── */

    /** Países habilitados para la sala seleccionada. */
    const countries = ref<Country[]>([])

    /** Indica si se está ejecutando la petición de generación. */
    const loading = ref(false)

    /** Indica si se están cargando los países de la sala. */
    const loadingCountries = ref(false)

    /**
     * Porcentaje de comisión del vendedor.
     * Se obtiene del perfil del vendedor autenticado (actualmente inicializado en 0).
     */
    const commission = ref(0)

    /** País seleccionado por el vendedor para la venta. */
    const selectedCountry = ref<number | null>(null)

    /** Cantidad de cartones a generar (mínimo 1, máximo 40). */
    const numCtns = ref(1)

    /** Datos del jugador que adquiere los cartones. */
    const player = reactive<Player>({
        phone: '',
        name: '',
        lastName: ''
    })

    /* ───────── COMPUTED ───────── */

    /**
     * Precio unitario del cartón según la configuración de la sala.
     * Retorna 0 si no hay sala seleccionada o el precio no está definido.
     */
    const roomCtnPrice = computed(() =>
        Number(selectedRoom.value?.config?.card_price_usd ?? 0)
    )

    /** Monto total a cobrar al jugador (precio × cantidad). */
    const totalAmount = computed(() => roomCtnPrice.value * numCtns.value)

    /** Comisión que retiene el vendedor sobre el monto total. */
    const sellerCommission = computed(() => totalAmount.value * (commission.value / 100))

    /** Monto neto que recibe el comercio (total − comisión). */
    const commerceAmount = computed(() => totalAmount.value - sellerCommission.value)

    /**
     * Indica si el formulario está listo para generar cartones.
     * Requiere sala seleccionada, país, cantidad válida y datos completos del jugador.
     */
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

    /**
     * Resetea el estado dependiente de la sala:
     * país, cantidad de cartones, lista de países y datos del jugador.
     */
    function resetState() {
        selectedCountry.value = null
        numCtns.value = 1
        countries.value = []
        player.phone = ''
        player.name = ''
        player.lastName = ''
    }

    /* ───────── WATCHERS ───────── */

    /**
     * Reacciona al cambio de sala seleccionada.
     * Resetea el estado y carga los países disponibles para la nueva sala.
     */
    watch(selectedRoom, async (room) => {
        resetState()
        if (room) await fetchCountries(room.id)
    })

    /* ───────── API ───────── */

    /**
     * Obtiene los países habilitados para operar en la sala indicada.
     *
     * @param roomId - ID de la sala seleccionada.
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
            showToast('No se pudieron cargar los países disponibles.', 'error')
        }
        finally {
            loadingCountries.value = false
        }
    }

    /**
     * Envía la petición para generar los cartones de bingo.
     *
     * Valida el formulario antes de proceder.
     * Resetea el estado del jugador al completarse con éxito.
     *
     * @returns `true` si los cartones fueron generados correctamente, `false` en caso contrario.
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
