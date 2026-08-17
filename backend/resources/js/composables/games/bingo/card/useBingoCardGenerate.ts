import { ref, computed, reactive, watch } from 'vue'
import type { Ref } from 'vue'

import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'
import { validateBingoCardGenerate } from './bingoCardGenerateSchema'

import type { Country } from '@/js/types/country'
import type {
    CardPriceResponse,
    GenerateCardPayload,
    GenerateCardResponse,
    Player,
    Room
} from '@/js/types/games/bingo/card/bingo-card-generate'

/**
 * Composable encargado de la lógica de generación de cartones de bingo.
 *
 * Responsabilidades:
 * - Obtener los países disponibles al seleccionar una sala.
 * - Consultar el precio del cartón según el país seleccionado (moneda).
 * - Gestionar los datos del jugador, cantidad de cartones y país.
 * - Calcular montos totales, comisión del vendedor y monto al comercio.
 * - Validar el formulario antes de enviar.
 * - Ejecutar la petición de generación de cartones.
 *
 * @param selectedRoom - Ref reactiva a la sala seleccionada, provista por
 *   `useBingoCardGenerateRoomSection` vía el componente padre.
 * @returns Estado y métodos necesarios para `BingoCardGenerateFormPlayerSection`
 *   y el formulario principal.
 */
export function useBingoCardGenerate(selectedRoom: Ref<Room | null>) {

    /* ───────── STATE ───────── */

    /** Países habilitados para la sala seleccionada. */
    const countries = ref<Country[]>([])

    /** Indica si se está ejecutando la petición de generación. */
    const loading = ref(false)

    /** Indica si se están cargando los países de la sala. */
    const loadingCountries = ref(false)

    /** Indica si se está consultando el precio del cartón para el país elegido. */
    const loadingCardPrice = ref(false)


    /**
     * País seleccionado por el vendedor para la venta.
     * Se mantiene como string porque los IDs de país son Snowflakes (> MAX_SAFE_INTEGER).
     */
    const selectedCountry = ref<string | null>(null)

    /** Cantidad de cartones a generar (mínimo 1, máximo 40). */
    const cardCount = ref(1)

    /** Datos del jugador que adquiere los cartones. */
    const player = reactive<Player>({
        phone: '',
        firstName: '',
        lastName: '',
        receiptNumber: '',
        receiptImage: null
    })

    /**
     * Precio del cartón obtenido desde el backend para la sala + país seleccionados.
     * `null` mientras no se haya consultado o si no hay país seleccionado.
     */
    const cardPriceData = ref<CardPriceResponse | null>(null)

    /**
     * Link público de acceso al juego para el último jugador externo generado.
     * Se debe mostrar al vendedor para que lo comparta con el comprador — sin
     * cuenta, es la única forma que tiene de ver sus cartones y el sorteo
     * (ver ExternalPlayerGameController).
     */
    const generatedPlayerLink = ref<string | null>(null)

    /* ───────── COMPUTED ───────── */

    /**
     * Precio unitario real del cartón en la moneda local.
     * Usa el precio devuelto por la API; 0 si aún no se consultó.
     */
    const roomCardPrice = computed(() => cardPriceData.value?.card_price ?? 0)

    /**
     * Símbolo de la moneda del país seleccionado (ej. '$', 'Bs', 'R$').
     * Vacío mientras no se haya consultado el precio.
     */
    const currencySymbol = computed(() => cardPriceData.value?.currency_symbol ?? '')

    /**
     * Porcentaje de comisión del vendedor autenticado.
     * Proviene del campo `commission` del seller, devuelto por el endpoint de precio.
     */
    const commission = computed(() => cardPriceData.value?.seller_commission ?? 0)

    /** Monto total a cobrar al jugador (precio unitario × cantidad). */
    const totalAmount = computed(() => roomCardPrice.value * cardCount.value)

    /** Comisión que retiene el vendedor sobre el monto total. */
    const sellerCommission = computed(() => totalAmount.value * (commission.value / 100))

    /** Monto neto que recibe el comercio (total − comisión). */
    const commerceAmount = computed(() => totalAmount.value - sellerCommission.value)

    /**
     * Indica si el formulario está listo para generar cartones.
     * Requiere sala, país, precio cargado, cantidad válida, datos completos del jugador y comprobante.
     */
    const isFormValid = computed(() =>
        validateBingoCardGenerate(
            {
                selectedCountry: selectedCountry.value,
                cardCount:         cardCount.value,
                phone:           player.phone,
                firstName:       player.firstName,
                lastName:        player.lastName,
                receiptNumber:   player.receiptNumber,
                receiptImage:    player.receiptImage,
            },
            {
                hasRoom:        !!selectedRoom.value,
                isLoadingPrice: loadingCardPrice.value,
                hasPriceLoaded: !!cardPriceData.value,
            }
        )
    )

    /* ───────── HELPERS ───────── */

    /**
     * Resetea el estado dependiente de la sala:
     * país, cantidad de cartones, lista de países, precio y datos del jugador.
     */
    function resetState() {
        selectedCountry.value = null
        cardCount.value = 1
        countries.value = []
        cardPriceData.value = null
        player.phone = ''
        player.firstName = ''
        player.lastName = ''
        player.receiptNumber = ''
        player.receiptImage = null
        generatedPlayerLink.value = null
    }

    /**
     * Resetea solo los campos del jugador y cantidad después de una generación exitosa,
     * pero mantiene el país seleccionado para facilitar generaciones consecutivas.
     */
    function resetAfterGeneration() {
        cardCount.value = 1
        player.phone = ''
        player.firstName = ''
        player.lastName = ''
        player.receiptNumber = ''
        player.receiptImage = null
        // Mantiene selectedCountry.value y countries.value
    }

    /**
     * Resetea únicamente el precio del cartón cuando se cambia de país
     * pero la sala no cambia.
     */
    function resetCardPrice() {
        cardPriceData.value = null
    }

    /* ───────── WATCHERS ───────── */

    /**
     * Reacciona al cambio de sala seleccionada.
     * Resetea todo el estado y carga los países disponibles para la nueva sala.
     */
    watch(selectedRoom, async (room) => {
        resetState()
        if (room) await fetchCountries(room.id)
    })

    /**
     * Reacciona al cambio de país seleccionado.
     * Consulta el precio del cartón según la moneda del nuevo país.
     */
    watch(selectedCountry, async (countryId) => {
        resetCardPrice()
        if (selectedRoom.value && countryId !== null) {
            await fetchCardPrice(selectedRoom.value.id, countryId)
        }
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
     * Consulta el precio del cartón para la sala y país seleccionados.
     *
     * El backend calcula el precio en la moneda local del país aplicando:
     * 1. Precio personalizado en `bingo_card_prices` (si existe).
     * 2. En su defecto, precio base de la sala (card_price_usd) × tasa de cambio del comercio.
     *
     * @param roomId    - ID de la sala seleccionada.
     * @param countryId - ID del país seleccionado.
     */
    async function fetchCardPrice(roomId: string, countryId: string) {
        loadingCardPrice.value = true
        try {
            const { data } = await backendClient.get<CardPriceResponse>(
                `/bingo-card/${roomId}/card-price`,
                { params: { country_id: countryId } }
            )
            cardPriceData.value = data
        }
        catch {
            cardPriceData.value = null
            showToast('No se pudo obtener el precio del cartón para el país seleccionado.', 'error')
        }
        finally {
            loadingCardPrice.value = false
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

        const roomHashedId = selectedRoom.value!.id
        
        // Crear FormData para enviar el archivo
        const formData = new FormData()
        formData.append('card_count', cardCount.value.toString())
        formData.append('country_id', selectedCountry.value!)
        formData.append('player[phone]', player.phone.trim())
        formData.append('player[first_name]', player.firstName.trim())
        formData.append('player[last_name]', player.lastName.trim())
        formData.append('receipt_number', player.receiptNumber!.trim())
        
        if (player.receiptImage) {
            formData.append('receipt_image', player.receiptImage)
        }

        try {
            const { data } = await backendClient.post<GenerateCardResponse>(`/bingo-card/${roomHashedId}/generate`, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            })
            showToast(data.message, 'success')
            generatedPlayerLink.value = data.playerLink ?? null
            resetAfterGeneration()
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
        cardCount,
        player,
        loading,
        loadingCountries,
        loadingCardPrice,
        cardPriceData,
        currencySymbol,
        totalAmount,
        sellerCommission,
        commerceAmount,
        isFormValid,
        generatedPlayerLink,
        generate
    }
}
