import { ref, computed } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'
import type { BingoGameRoomDetail } from '@/js/types/games/bingo/room/game-rooms/bingo-game-room'

// ---------------------------------------------------------------------------
// Tipos locales
// ---------------------------------------------------------------------------

/**
 * Parámetros del contexto del jugador/creador necesarios para las requests.
 */
export interface CardPurchaseContext {
  /** ID del creador de la sala */
  creatorId: string | number
  /** Tipo de creador: 'commerce_id' | 'RKE' */
  creatorType: string
  /** Identificador del jugador */
  player: string | null
  /** Moneda seleccionada */
  currency: string | null
  /** Usuario creador (para la ruta de datetime) */
  creatorUser?: string | null
}

/**
 * Respuesta de la compra de cartones.
 */
interface PurchaseResponse {
  message: string
  type: 'success' | 'error' | 'warning' | 'info'
  prize_pool?: number
  ctns?: number
}

// ---------------------------------------------------------------------------
// Composable
// ---------------------------------------------------------------------------

/**
 * Composable que gestiona la selección de sala y compra de cartones.
 *
 * Responsabilidades:
 * - Fetch de detalle de sala al hacer clic (`fetchRoomDetail`)
 * - Cálculo del total según número de cartones
 * - Envío de compra al backend (`purchase`)
 * - Estado de visibilidad del panel de compra
 *
 * @param context - Contexto del jugador/creador
 */
export function useGameRoomCardPurchase(context: CardPurchaseContext) {
  // -------------------------------------------------------------------------
  // Estado
  // -------------------------------------------------------------------------

  /** ID de la sala seleccionada (encrypted o UUID) */
  const selectedRoomId = ref<string | null>(null)

  /** Detalle de la sala obtenido del backend */
  const roomDetail = ref<BingoGameRoomDetail | null>(null)

  /** Número de cartones a comprar */
  const numCtns = ref(1)

  /** Si el panel de compra está visible */
  const showPurchasePanel = ref(false)

  /** Estado de carga */
  const loading = ref(false)

  /** Si el usuario está logueado (viene del backend) */
  const isLogged = ref(false)

  // -------------------------------------------------------------------------
  // Computed
  // -------------------------------------------------------------------------

  /**
   * Total a pagar según precio de cartón y cantidad.
   */
  const totalAmount = computed<number>(() => {
    if (!roomDetail.value) return 0
    return parseFloat((numCtns.value * roomDetail.value.cardPrice).toFixed(2))
  })

  /**
   * Etiqueta del tipo de premio: 'Bote' o 'Premio'.
   */
  const prizePoolLabel = computed<string>(() => {
    return roomDetail.value?.prizePoolType ?? 'Premio'
  })

  // -------------------------------------------------------------------------
  // Métodos
  // -------------------------------------------------------------------------

  /**
   * Abre el panel de compra para la sala seleccionada.
   * Hace fetch del detalle de la sala al backend.
   *
   * @param roomId - ID encriptado o UUID de la sala
   */
  async function fetchRoomDetail(roomId: string): Promise<void> {
    if (loading.value) return

    try {
      loading.value = true
      selectedRoomId.value = roomId
      numCtns.value = 1
      showPurchasePanel.value = false

      const { data } = await backendClient.get<{
        isLogged: number
        room: BingoGameRoomDetail
      }>('/bingo/info-encrypted', {
        params: {
          id: roomId,
          creatorUser: context.creatorUser,
          currency: context.currency,
        },
      })

      roomDetail.value = data.room
      isLogged.value = Boolean(data.isLogged)
      showPurchasePanel.value = true
    } catch (err) {
      console.error('[useGameRoomCardPurchase] fetchRoomDetail error:', err)
      showToast('Error al cargar la información de la sala', 'error')
    } finally {
      loading.value = false
    }
  }

  /**
   * Cierra el panel de compra y limpia el estado.
   */
  function closePurchasePanel(): void {
    showPurchasePanel.value = false
    selectedRoomId.value = null
    roomDetail.value = null
    numCtns.value = 1
  }

  /**
   * Incrementa el número de cartones respetando el máximo (30).
   */
  function incrementCtns(): void {
    if (numCtns.value < 30) numCtns.value++
  }

  /**
   * Decrementa el número de cartones respetando el mínimo (1).
   */
  function decrementCtns(): void {
    if (numCtns.value > 1) numCtns.value--
  }

  /**
   * Establece directamente el número de cartones validando el rango [1..30].
   * @param value - Valor a establecer
   */
  function setNumCtns(value: number): void {
    numCtns.value = Math.min(30, Math.max(1, Math.floor(value) || 1))
  }

  /**
   * Envía la compra de cartones al backend.
   * Emite eventos de UI mediante `showToast`.
   *
   * @param onSuccess - Callback llamado con el resultado si la compra fue exitosa
   */
  async function purchase(
    onSuccess?: (result: { prizePool: number; ctns: number }) => void,
  ): Promise<void> {
    if (loading.value || !selectedRoomId.value) return
    if (isLogged.value || context.player === 'GAMES') return

    try {
      loading.value = true

      const { data } = await backendClient.post<PurchaseResponse>(
        '/bingo/generate-card',
        {
          room: selectedRoomId.value,
          currency: context.currency,
          num_ctns: numCtns.value,
          creator_type: context.creatorType,
          creator_id: context.creatorId,
          player: context.player,
          is_login: true,
        },
      )

      showToast(data.message, data.type)

      if (data.type === 'success') {
        onSuccess?.({
          prizePool: data.prize_pool ?? 0,
          ctns: data.ctns ?? numCtns.value,
        })
      }
    } catch (err: any) {
      console.error('[useGameRoomCardPurchase] purchase error:', err)
      showToast(
        err?.response?.data?.message ?? 'Error al generar los cartones',
        'error',
      )
    } finally {
      loading.value = false
    }
  }

  return {
    /** ID de la sala seleccionada */
    selectedRoomId,
    /** Detalle de la sala seleccionada */
    roomDetail,
    /** Número de cartones a comprar */
    numCtns,
    /** Si el panel de compra está visible */
    showPurchasePanel,
    /** Estado de carga */
    loading,
    /** Total a pagar */
    totalAmount,
    /** Etiqueta de tipo de premio */
    prizePoolLabel,
    /** Carga el detalle de una sala y abre el panel */
    fetchRoomDetail,
    /** Cierra el panel y limpia el estado */
    closePurchasePanel,
    /** Incrementa cartones */
    incrementCtns,
    /** Decrementa cartones */
    decrementCtns,
    /** Establece directamente el número de cartones */
    setNumCtns,
    /** Envía la compra al backend */
    purchase,
  }
}
