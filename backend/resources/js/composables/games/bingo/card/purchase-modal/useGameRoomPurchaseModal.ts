import { ref, computed } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'
import type {
  BingoAvailableCountry,
  BingoCardPriceResponse,
  BingoCardPurchaseResponse,
  PurchaseModalStep,
} from '@/js/types/games/bingo/card/purchase-modal/bingo-purchase-modal'
import type { BingoGameRoom } from '@/js/types/games/bingo/room/game-rooms/bingo-game-room'

// ─────────────────────────────────────────────────────────────────────────────
// Composable
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Gestiona el flujo de compra de cartones desde la vista game-rooms.
 *
 * Flujo de 2 pasos (sin datos del jugador — el player ya está en sesión):
 *  1. `country`  — seleccionar país y obtener precio del cartón
 *  2. `confirm`  — ajustar cantidad y confirmar compra
 *
 * Endpoints:
 *  - GET  /bingo-card/{room}/available-countries
 *  - GET  /bingo-card/{room}/card-price?country_id=
 *  - POST /bingo-game-rooms/{room}/buy
 *
 * Uso:
 * ```ts
 * const modal = useGameRoomPurchaseModal()
 * modal.open(room)
 * ```
 */
export function useGameRoomPurchaseModal() {
  // ── Visibilidad ──────────────────────────────────────────────────────────
  const isOpen = ref(false)
  const step   = ref<PurchaseModalStep>('country')

  // ── Sala seleccionada ────────────────────────────────────────────────────
  const selectedRoom = ref<BingoGameRoom | null>(null)

  // ── Países disponibles ───────────────────────────────────────────────────
  const countries        = ref<BingoAvailableCountry[]>([])
  const loadingCountries = ref(false)
  const selectedCountry  = ref<BingoAvailableCountry | null>(null)

  // ── Precio del cartón ────────────────────────────────────────────────────
  const cardPrice      = ref(0)
  const currencySymbol = ref('')
  const loadingPrice   = ref(false)

  // ── Cantidad ─────────────────────────────────────────────────────────────
  const numCtns  = ref(1)
  const MIN_CTNS = 1
  const MAX_CTNS = 40

  // ── Estado de compra ─────────────────────────────────────────────────────
  const purchasing = ref(false)

  // ─────────────────────────────────────────────────────────────────────────
  // Computed
  // ─────────────────────────────────────────────────────────────────────────

  /** Total a pagar */
  const totalAmount = computed(() =>
    parseFloat((numCtns.value * cardPrice.value).toFixed(2)),
  )

  /** Si se puede avanzar al paso de confirmación */
  const canAdvance = computed(() => {
    if (step.value === 'country') return selectedCountry.value !== null && cardPrice.value > 0
    return true
  })

  // ─────────────────────────────────────────────────────────────────────────
  // Métodos privados
  // ─────────────────────────────────────────────────────────────────────────

  /** Carga los países disponibles para la sala */
  async function _fetchCountries(roomId: string): Promise<void> {
    try {
      loadingCountries.value = true
      const { data } = await backendClient.get<{ data: BingoAvailableCountry[] }>(
        `/bingo-card/${roomId}/available-countries`,
      )
      countries.value = data.data ?? []
    } catch {
      showToast('Error al cargar los países disponibles', 'error')
      countries.value = []
    } finally {
      loadingCountries.value = false
    }
  }

  /** Carga el precio del cartón para el país seleccionado */
  async function _fetchCardPrice(roomId: string, countryId: string): Promise<void> {
    try {
      loadingPrice.value = true
      const { data } = await backendClient.get<BingoCardPriceResponse>(
        `/bingo-card/${roomId}/card-price`,
        { params: { country_id: countryId } },
      )
      cardPrice.value      = data.card_price
      currencySymbol.value = data.currency_symbol
    } catch {
      showToast('Error al obtener el precio del cartón', 'error')
      cardPrice.value = 0
    } finally {
      loadingPrice.value = false
    }
  }

  // ─────────────────────────────────────────────────────────────────────────
  // API pública
  // ─────────────────────────────────────────────────────────────────────────

  /**
   * Abre el modal para una sala y carga sus países disponibles.
   * @param room - Sala seleccionada
   */
  async function open(room: BingoGameRoom): Promise<void> {
    selectedRoom.value    = room
    selectedCountry.value = null
    cardPrice.value       = 0
    currencySymbol.value  = ''
    numCtns.value         = 1
    step.value            = 'country'
    isOpen.value          = true

    await _fetchCountries(room.id)
  }

  /** Cierra el modal y limpia el estado. */
  function close(): void {
    isOpen.value          = false
    selectedRoom.value    = null
    selectedCountry.value = null
    countries.value       = []
  }

  /**
   * Selecciona un país y obtiene el precio del cartón.
   * @param country - País seleccionado
   */
  async function selectCountry(country: BingoAvailableCountry): Promise<void> {
    selectedCountry.value = country
    if (!selectedRoom.value) return
    await _fetchCardPrice(selectedRoom.value.id, country.id)
  }

  /** Avanza al paso de confirmación. */
  function nextStep(): void {
    if (step.value === 'country') step.value = 'confirm'
  }

  /** Retrocede al paso de selección de país. */
  function prevStep(): void {
    if (step.value === 'confirm') step.value = 'country'
  }

  /** Incrementa la cantidad de cartones (máx 40). */
  function increment(): void {
    if (numCtns.value < MAX_CTNS) numCtns.value++
  }

  /** Decrementa la cantidad de cartones (mín 1). */
  function decrement(): void {
    if (numCtns.value > MIN_CTNS) numCtns.value--
  }

  /**
   * Establece la cantidad de cartones validando el rango [1..40].
   * @param value - Valor a establecer
   */
  function setNumCtns(value: number): void {
    numCtns.value = Math.min(MAX_CTNS, Math.max(MIN_CTNS, Math.floor(value) || MIN_CTNS))
  }

  /**
   * Envía la compra al endpoint exclusivo de game-rooms.
   * POST /bingo-game-rooms/{room}/buy
   * @param onSuccess - Callback si la compra fue exitosa
   */
  async function purchase(
    onSuccess?: (result: { prizePool: number; ctns: number }) => void,
  ): Promise<void> {
    if (purchasing.value || !selectedRoom.value || !selectedCountry.value) return

    try {
      purchasing.value = true

      const { data } = await backendClient.post<BingoCardPurchaseResponse>(
        `/bingo-game-rooms/${selectedRoom.value.id}/buy`,
        {
          num_ctns:   numCtns.value,
          country_id: selectedCountry.value.id,
        },
      )

      showToast(data.message, data.type)

      if (data.type === 'success') {
        onSuccess?.({
          prizePool: data.prize_pool ?? 0,
          ctns:      data.ctns ?? numCtns.value,
        })
        close()
      }
    } catch (err: any) {
      showToast(
        err?.response?.data?.message ?? 'Error al generar los cartones',
        'error',
      )
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
    open,
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
