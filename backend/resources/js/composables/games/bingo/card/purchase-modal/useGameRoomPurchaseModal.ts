import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import { SWAL_DARK } from '@js/utils/swal'
import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'
import { getBingoVariantDef } from '@/js/modules/games/bingo/utils/bingoVariants'
import { useGameRoomRoles } from '@js/composables/games/bingo/room/game-rooms/useGameRoomRoles'
import type {
    BingoAvailableCountry,
    BingoCardPriceResponse,
    BingoCardPurchaseResponse,
    PurchaseModalStep,
} from '@/js/types/games/bingo/card/purchase-modal/bingo-purchase-modal'
import type { BingoGameRoom } from '@/js/types/games/bingo/room/game-rooms/bingo-game-room'

type CardMatrix = Record<string, (number | null)[]>

/** Matriz vacía (todo null, salvo que no exista casilla libre) para una variante dada. */
function emptyMatrix(variant?: string | null): CardMatrix {
    const def = getBingoVariantDef(variant)
    const matrix: CardMatrix = {}
    for (const col of def.columns) {
        matrix[col.label] = Array(def.numRows).fill(null)
    }
    return matrix
}

/** Estado de la modalidad de cartón personalizado, resuelto por el backend en /player-card-price. */
interface CustomCardState {
    mode: 'paid' | 'every_x' | 'free' | null
    pricePct: number
    everyX: number | null
}

const EMPTY_CUSTOM_CARD_STATE: CustomCardState = { mode: null, pricePct: 0, everyX: null }

// Caché en módulo: sobrevive navegación dentro de la SPA pero se limpia al recargar
const _priceCache = new Map<string, { price: number; symbol: string; ts: number; customCard: CustomCardState }>()
const _inflight = new Map<string, Promise<BingoCardPriceResponse>>()
const CACHE_TTL_MS = 60_000 // 1 minuto

function _cachedPrice(roomId: string) {
    const entry = _priceCache.get(roomId)
    if (!entry) return null
    if (Date.now() - entry.ts > CACHE_TTL_MS) { _priceCache.delete(roomId); return null }
    return entry
}

/**
 * Clave de ruta para todos los endpoints que usan HasHashedRouteKey (Hashids):
 *  - /bingo-card/{room}/*
 *  - /bingo/game-rooms/{room}/*  (player-card-price, buy, player-buy)
 *
 * room.encrypted es el hashed_id que el backend espera en el route model binding.
 * Fallback a room.id solo como último recurso (p.ej. si el prop no llegó).
 */
function _bingoCardKey(room: BingoGameRoom): string {
    return room.encrypted ?? room.id
}

/**
 * Petición HTTP deduplicada: si ya hay una en vuelo para el mismo room
 * reutiliza la misma Promise en lugar de lanzar una segunda.
 *
 * Usa room.id como clave de caché (estable entre rerenders) pero _bingoCardKey(room)
 * como segmento de URL (hashed_id que el route model binding de Laravel espera).
 */
function _fetchPlayerCardPriceHttp(room: BingoGameRoom): Promise<BingoCardPriceResponse> {
    const cacheKey = room.id
    if (_inflight.has(cacheKey)) return _inflight.get(cacheKey)!
    const routeKey = _bingoCardKey(room)
    const req = backendClient
        .get<BingoCardPriceResponse>(`/bingo/game-rooms/${routeKey}/player-card-price`)
        .then(r => r.data)
        .finally(() => _inflight.delete(cacheKey))
    _inflight.set(cacheKey, req)
    return req
}

export function useGameRoomPurchaseModal() {
    // OPT-03: reutilizar useGameRoomRoles en lugar de duplicar la lógica de isPlayer
    const { isPlayer } = useGameRoomRoles()

    // ── Visibilidad ──────────────────────────────────────────────────────────
    const isOpen = ref(false)
    const step = ref<PurchaseModalStep>('loading')

    // ── Sala seleccionada ────────────────────────────────────────────────────
    const selectedRoom = ref<BingoGameRoom | null>(null)

    // ── Países disponibles ───────────────────────────────────────────────────
    const countries = ref<BingoAvailableCountry[]>([])
    const loadingCountries = ref(false)
    const selectedCountry = ref<BingoAvailableCountry | null>(null)

    // ── Precio del cartón ────────────────────────────────────────────────────
    const cardPrice = ref(0)
    const currencySymbol = ref('USD')
    const loadingPrice = ref(false)

    // ── Estado de cartón personalizado (resuelto por el backend) ─────────────
    const customCardState = ref<CustomCardState>({ ...EMPTY_CUSTOM_CARD_STATE })

    // ── Cantidad ─────────────────────────────────────────────────────────────
    const numCtns = ref(1)
    const MIN_CTNS = 1
    const MAX_CTNS = 40

    // ── Personalización de cartones ──────────────────────────────────────────
    const customizeCard = ref(false)
    // Array de matrices, una por cartón (índice 0..numCtns-1).
    const customCardNumbers = ref<CardMatrix[]>([])
    // Por cartón: true si el usuario tocó ≥1 casilla manualmente (no solo autocompletar).
    const manualEditsPerCard = ref<boolean[]>([])
    // Índice del cartón que se está editando actualmente en el picker.
    const activeCardIndex = ref(0)

    const canCustomize = computed(
        () => isPlayer.value && selectedRoom.value?.config?.allow_custom_card === true,
    )

    // True mientras la sala está seleccionada pero su config aún no llegó (lazy prop de Inertia).
    const configLoading = computed(
        () => !!selectedRoom.value && !selectedRoom.value.config?.game_variant,
    )

    // Recargo % configurado en la sala (0 si no aplica; solo relevante en modo 'paid').
    const customPricePct = computed(() => customCardState.value.pricePct)

    /**
     * Máximo de cartones personalizables permitidos en esta compra cuando el modo
     * es 'every_x': por cada ciclo de (X normales + 1 personalizable) solo el
     * último cartón del ciclo puede personalizarse. Ej. X=3 con 10 cartones →
     * de cada 4 consecutivos, 1 personalizable (posiciones 4, 8) → máximo 2.
     * En 'paid'/'free' no hay límite (más allá de numCtns).
     */
    const maxCustomCardsAllowed = computed(() => {
        if (customCardState.value.mode !== 'every_x') return numCtns.value
        const everyX = customCardState.value.everyX ?? 0
        if (everyX < 1) return numCtns.value
        return Math.floor(numCtns.value / (everyX + 1))
    })

    // Solo las matrices que el usuario editó manualmente — las demás van como null (aleatorio).
    const effectiveCustomMatrices = computed<(CardMatrix | null)[]>(() => {
        if (!customizeCard.value) return []
        return customCardNumbers.value.map((m, i) => manualEditsPerCard.value[i] ? m : null)
    })

    // Cuántos cartones son realmente personalizados (editados manualmente).
    const manualCardCount = computed(
        () => effectiveCustomMatrices.value.filter(m => m !== null).length,
    )

    // True si el jugador personalizó más cartones de los permitidos por el modo 'every_x'.
    const exceedsCustomCardLimit = computed(
        () => customCardState.value.mode === 'every_x' && manualCardCount.value > maxCustomCardsAllowed.value,
    )

    // Precio unitario base.
    const basePricePerCard = computed(() => cardPrice.value)

    // Precio unitario de cartón personalizado: recargo solo aplica en modo 'paid'.
    const customPricePerCard = computed(() => {
        if (customCardState.value.mode !== 'paid' || customPricePct.value <= 0) return cardPrice.value
        return parseFloat((cardPrice.value * (1 + customPricePct.value / 100)).toFixed(4))
    })

    // Total calculado según distribución manual/aleatorio.
    const totalAmountWithCustom = computed(() => {
        if (!customizeCard.value || manualCardCount.value === 0) {
            return parseFloat((numCtns.value * basePricePerCard.value).toFixed(2))
        }
        const regularCost = (numCtns.value - manualCardCount.value) * basePricePerCard.value
        const customCost  = manualCardCount.value * customPricePerCard.value
        return parseFloat((regularCost + customCost).toFixed(2))
    })

    function _buildEmptyMatrices(n: number, variant?: string | null): CardMatrix[] {
        return Array.from({ length: n }, () => emptyMatrix(variant))
    }

    // ── Estado de compra ─────────────────────────────────────────────────────
    const purchasing = ref(false)

    // ── Computed ─────────────────────────────────────────────────────────────

    // totalAmount delega al cálculo con recargo cuando hay cartones personalizados.
    const totalAmount = totalAmountWithCustom

    const canAdvance = computed(() => {
        if (isPlayer.value) return true
        if (step.value === 'country') return selectedCountry.value !== null && cardPrice.value > 0
        return true
    })

    // ── Fetch privados ───────────────────────────────────────────────────────

    async function _fetchCountries(room: BingoGameRoom): Promise<void> {
        try {
            loadingCountries.value = true
            const { data } = await backendClient.get<{ data: BingoAvailableCountry[] }>(
                `/bingo-card/${_bingoCardKey(room)}/available-countries`,
            )
            countries.value = data.data ?? []
            step.value = 'country'
        } catch {
            showToast('Error al cargar los países disponibles', 'error')
            countries.value = []
        } finally {
            loadingCountries.value = false
        }
    }

    async function _fetchCardPrice(room: BingoGameRoom, countryId: string): Promise<void> {
        try {
            loadingPrice.value = true
            const { data } = await backendClient.get<BingoCardPriceResponse>(
                `/bingo-card/${_bingoCardKey(room)}/card-price`,
                { params: { country_id: countryId } },
            )
            cardPrice.value = data.card_price
            currencySymbol.value = data.currency_symbol
        } catch {
            showToast('Error al obtener el precio del cartón', 'error')
            cardPrice.value = 0
        } finally {
            loadingPrice.value = false
        }
    }

    async function _fetchPlayerCardPrice(room: BingoGameRoom): Promise<void> {
        const cacheKey = room.id
        const cached = _cachedPrice(cacheKey)
        if (cached) {
            cardPrice.value = cached.price
            currencySymbol.value = cached.symbol
            step.value = 'confirm'
            return
        }

        try {
            loadingPrice.value = true
            const data = await _fetchPlayerCardPriceHttp(room)
            cardPrice.value = data.card_price
            currencySymbol.value = data.currency_symbol
            step.value = 'confirm'
            _priceCache.set(cacheKey, { price: data.card_price, symbol: data.currency_symbol, ts: Date.now() })
        } catch {
            showToast('Error al obtener el precio del cartón', 'error')
            cardPrice.value = 0
        } finally {
            loadingPrice.value = false
        }
    }

    // ── API pública ───────────────────────────────────────────────────────────

    /**
     * Pre-carga el precio del cartón en segundo plano al pasar el cursor / abrir
     * el modal de info, para que cuando el usuario presione "Comprar" ya esté listo.
     */
    async function prefetch(room: BingoGameRoom): Promise<void> {
        if (!isPlayer.value) return
        if (_cachedPrice(room.id) || _inflight.has(room.id)) return
        try {
            const data = await _fetchPlayerCardPriceHttp(room)
            _priceCache.set(room.id, { price: data.card_price, symbol: data.currency_symbol, ts: Date.now() })
        } catch {
            // silencioso — es una optimización, no crítico
        }
    }

    /**
     * Abre el modal de compra. Si el precio ya está en caché (gracias a prefetch),
     * el paso 'loading' dura ~0ms.
     */
    async function open(room: BingoGameRoom): Promise<void> {
        selectedRoom.value = room
        selectedCountry.value = null
        numCtns.value = 1
        customizeCard.value = false
        activeCardIndex.value = 0
        customCardNumbers.value = _buildEmptyMatrices(1, room.config?.game_variant)
        manualEditsPerCard.value = [false]

        if (isPlayer.value) {
            // Precio en caché → paso inmediato; si no → fetch único
            const cached = _cachedPrice(room.id)
            if (cached) {
                cardPrice.value = cached.price
                currencySymbol.value = cached.symbol
                step.value = 'confirm'
                isOpen.value = true
            } else {
                cardPrice.value = 0
                step.value = 'loading'
                isOpen.value = true
                await _fetchPlayerCardPrice(room)
            }
        } else {
            cardPrice.value = 0
            step.value = 'loading'
            isOpen.value = true
            await _fetchCountries(room)
        }
    }

    function close(): void {
        isOpen.value = false
        step.value = 'loading'
        selectedRoom.value = null
        selectedCountry.value = null
        countries.value = []
        customizeCard.value = false
        customCardNumbers.value = []
        manualEditsPerCard.value = []
        activeCardIndex.value = 0
    }

    function toggleCustomize(): void {
        if (!canCustomize.value) return
        customizeCard.value = !customizeCard.value
        activeCardIndex.value = 0
        if (selectedRoom.value) {
            customCardNumbers.value = _buildEmptyMatrices(numCtns.value, selectedRoom.value.config?.game_variant)
        }
        manualEditsPerCard.value = Array(numCtns.value).fill(false)
    }

    /**
     * Si el cartón personalizado quedó incompleto, pregunta al usuario si desea
     * autocompletar las casillas faltantes o cancelar para seguir editando.
     * Devuelve true si la compra puede continuar (completo o ya autocompletado).
     */
    async function confirmIncompleteCustomCard(
        missingCount: number,
        autoFill: () => void,
    ): Promise<boolean> {
        if (!customizeCard.value || missingCount === 0) return true

        const result = await Swal.fire({
            title: 'Cartón incompleto',
            text: `Faltan ${missingCount} casilla${missingCount === 1 ? '' : 's'} por elegir. ¿Deseas autocompletarlas al azar o seguir editando?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Autocompletar',
            cancelButtonText: 'Seguir editando',
            confirmButtonColor: '#2af598',
            cancelButtonColor: '#6c757d',
            ...SWAL_DARK,
        })

        if (!result.isConfirmed) return false

        autoFill()
        return true
    }

    async function selectCountry(country: BingoAvailableCountry): Promise<void> {
        selectedCountry.value = country
        if (!selectedRoom.value) return
        await _fetchCardPrice(selectedRoom.value, country.id)
    }

    function nextStep(): void {
        if (step.value === 'country') step.value = 'confirm'
    }

    function prevStep(): void {
        if (step.value === 'confirm') step.value = 'country'
    }

    function _resizeMatrices(n: number): void {
        if (!customizeCard.value) return
        const variant = selectedRoom.value?.config?.game_variant
        const current = customCardNumbers.value
        const edits   = manualEditsPerCard.value
        if (n > current.length) {
            customCardNumbers.value  = [...current, ..._buildEmptyMatrices(n - current.length, variant)]
            manualEditsPerCard.value = [...edits, ...Array(n - edits.length).fill(false)]
        } else {
            customCardNumbers.value  = current.slice(0, n)
            manualEditsPerCard.value = edits.slice(0, n)
        }
        if (activeCardIndex.value >= n) activeCardIndex.value = n - 1
    }

    function increment(): void {
        if (numCtns.value < MAX_CTNS) { numCtns.value++; _resizeMatrices(numCtns.value) }
    }

    function decrement(): void {
        if (numCtns.value > MIN_CTNS) { numCtns.value--; _resizeMatrices(numCtns.value) }
    }

    function setNumCtns(value: number): void {
        numCtns.value = Math.min(MAX_CTNS, Math.max(MIN_CTNS, Math.floor(value) || MIN_CTNS))
        _resizeMatrices(numCtns.value)
    }

    async function purchase(
        onSuccess?: (result: { prizePool: number; ctns: number }) => void,
    ): Promise<void> {
        if (purchasing.value || !selectedRoom.value) return

        if (!isPlayer.value && !selectedCountry.value) {
            showToast('Debes seleccionar un país', 'error')
            return
        }

        // Invalidar caché antes del intento — el precio puede cambiar
        // independientemente del resultado (compra parcial, error de stock, etc.)
        const cacheKey = selectedRoom.value.id
        const routeKey = _bingoCardKey(selectedRoom.value)
        _priceCache.delete(cacheKey)

        try {
            purchasing.value = true

            const endpoint = isPlayer.value
                ? `/bingo/game-rooms/${routeKey}/player-buy`
                : `/bingo/game-rooms/${routeKey}/buy`

            // Solo enviar matrices con edición manual real (null = cartón aleatorio, no se envía)
            const matricesToSend = effectiveCustomMatrices.value.filter((m): m is CardMatrix => m !== null)

            const payload = isPlayer.value
                ? {
                    num_ctns: numCtns.value,
                    ...(canCustomize.value && customizeCard.value && matricesToSend.length > 0
                        ? { custom_numbers: matricesToSend }
                        : {}),
                }
                : { num_ctns: numCtns.value, country_id: selectedCountry.value!.id }


            const { data } = await backendClient.post<BingoCardPurchaseResponse>(endpoint, payload)

            showToast(data.message, data.type)

            if (data.type === 'success') {
                onSuccess?.({ prizePool: data.prize_pool ?? 0, ctns: data.ctns ?? numCtns.value })
                close()
            }
        } catch (err: any) {
            showToast(err?.response?.data?.message ?? 'Error al generar los cartones', 'error')
        } finally {
            purchasing.value = false
        }
    }

    return {
        isOpen,
        step,
        selectedRoom,
        countries,
        loadingCountries,
        selectedCountry,
        cardPrice,
        currencySymbol,
        loadingPrice,
        numCtns,
        purchasing,
        totalAmount,
        canAdvance,
        isPlayer,
        customizeCard,
        customCardNumbers,
        manualEditsPerCard,
        activeCardIndex,
        canCustomize,
        configLoading,
        customPricePct,
        manualCardCount,
        basePricePerCard,
        customPricePerCard,
        effectiveCustomMatrices,
        toggleCustomize,
        confirmIncompleteCustomCard,
        open,
        prefetch,
        close,
        selectCountry,
        nextStep,
        prevStep,
        increment,
        decrement,
        setNumCtns,
        purchase,
    }
}
