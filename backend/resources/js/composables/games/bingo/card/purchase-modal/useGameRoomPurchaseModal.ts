import { ref, computed } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'
import { AuthService } from '@/js/Services/authService'
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
  const currencySymbol = ref('USD')
  const loadingPrice   = ref(false)

  // ── Cantidad ─────────────────────────────────────────────────────────────
  const numCtns  = ref(1)
  const MIN_CTNS = 1
  const MAX_CTNS = 40

  // ── Estado de compra ─────────────────────────────────────────────────────
  const purchasing = ref(false)

  // ── Role detection ───────────────────────────────────────────────────────
  const isPlayer = ref<boolean | null>(null)
  const checkingRole = ref(false)

  /**
   * Verificar el rol del usuario desde el backend
   */
  async function checkUserRole(): Promise<boolean> {
    if (checkingRole.value) return isPlayer.value || false
    
    checkingRole.value = true
    
    try {
      const { data } = await backendClient.get('/bingo-game-rooms/check-user-role');
      isPlayer.value = data.is_player;
      return isPlayer.value;
    } catch (error) {
      console.error('Error checking user role:', error);
      // Fallback: assume player if backend check fails
      isPlayer.value = true;
      return true;
    } finally {
      checkingRole.value = false;
    }
  }

  // ─────────────────────────────────────────────────────────────────────────
  // Computed
  // ─────────────────────────────────────────────────────────────────────────

  /** Total a pagar */
  const totalAmount = computed(() =>
    parseFloat((numCtns.value * cardPrice.value).toFixed(2)),
  )

  /** Si se puede avanzar al paso de confirmación */
  const canAdvance = computed(() => {
    if (isPlayer.value === null) return false // Still checking role
    if (isPlayer.value) return true // Players can always advance
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

  /** Carga el precio del cartón para el jugador usando su billetera */
  async function _fetchPlayerCardPrice(roomId: string): Promise<void> {
    try {
      loadingPrice.value = true
      const { data } = await backendClient.get<BingoCardPriceResponse>(
        `/bingo-game-rooms/${roomId}/player-card-price`,
      )
      cardPrice.value      = data.card_price
      currencySymbol.value = data.currency_symbol
    } catch (error) {
      console.error('Error fetching player card price:', error);
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
   * Abre el modal para una sala y carga los datos necesarios.
   * Para jugadores: obtiene precio directamente y va al paso de confirmación
   * Para otros roles: carga países y empieza con selección de país
   * @param room - Sala seleccionada
   */
  async function open(room: BingoGameRoom): Promise<void> {
    selectedRoom.value    = room
    selectedCountry.value = null
    cardPrice.value       = 0
    currencySymbol.value  = 'USD'
    numCtns.value         = 1
    isOpen.value          = true

    // Verificar el rol desde el backend
    const userIsPlayer = await checkUserRole();

    if (userIsPlayer) {
      // Para jugadores: ir directamente al paso de confirmación
      step.value = 'confirm'
      await _fetchPlayerCardPrice(room.id)
    } else {
      // Para otros roles: cargar países y empezar con selección de país
      step.value = 'country'
      await _fetchCountries(room.id)
    }
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
   * Envía la compra al endpoint correspondiente.
   * Players usan endpoint específico sin country_id
   * Otros roles usan endpoint de game-rooms con country_id
   * @param onSuccess - Callback si la compra fue exitosa
   */
  async function purchase(
    onSuccess?: (result: { prizePool: number; ctns: number }) => void,
  ): Promise<void> {
    if (purchasing.value || !selectedRoom.value) return

    // Validaciones específicas por rol
    if (isPlayer.value !== true && !selectedCountry.value) {
      showToast('Debes seleccionar un país', 'error')
      return
    }

    try {
      purchasing.value = true

      let endpoint: string
      let payload: any

      if (isPlayer.value === true) {
        // Jugadores: endpoint específico sin country_id
        endpoint = `/bingo-game-rooms/${selectedRoom.value.id}/player-buy`
        payload = {
          num_ctns: numCtns.value,
        }
      } else {
        // Otros roles: endpoint original con country_id
        endpoint = `/bingo-game-rooms/${selectedRoom.value.id}/buy`
        payload = {
          num_ctns:   numCtns.value,
          country_id: selectedCountry.value!.id,
        }
      }

      const { data } = await backendClient.post<BingoCardPurchaseResponse>(
        endpoint,
        payload,
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
    isPlayer,
    checkingRole,
    open,
    close,
    selectCountry,
    nextStep,
    prevStep,
    increment,
    decrement,
    setNumCtns,
    purchase,
    checkUserRole,
  }
}
