import { ref, computed, type ComputedRef } from 'vue'
import type { Room, Player } from '@js/types/trivia'

interface JoinDeps {
  room: Room
  currentUserId: string
  userWallet?: { balance: number; symbol: string; currencyId: string; found: boolean } | null
  alreadyJoined: boolean
  authName: ComputedRef<string>
  requiresTeamSelection: ComputedRef<boolean>
  selectedTeamId: ComputedRef<string | null>
  onJoined: (player: Player) => void
}

/**
 * Flujo de unión a la sala (con o sin costo de entrada) + validaciones de
 * saldo/wallet para el fee. El POST de unión descuenta el fee del wallet
 * en el backend — este composable solo refleja el resultado.
 */
export function useTriviaRoomJoin(deps: JoinDeps) {
  const { room, currentUserId, userWallet, alreadyJoined, authName, requiresTeamSelection, selectedTeamId, onJoined } = deps

  const hasJoined = ref(alreadyJoined)
  const isLoading = ref(false)
  const joinError = ref<string | null>(null)
  const showConfirmModal = ref(false)

  const canJoin = computed(() =>
    requiresTeamSelection.value ? selectedTeamId.value !== null : true
  )

  // La sala tiene fee pero no tiene moneda configurada NI wallet convertida disponible
  // (error de configuración admin: no hay tasa de cambio / wallet para aplicar conversión)
  const feeWithoutCurrency = computed(() =>
    !!room.entryFee && room.entryFee > 0 && !room.entryCurrencyId && !userWallet
  )

  // Solo es "saldo insuficiente" si: hay wallet encontrada (directa o por conversión) y balance < fee
  const hasInsufficientFunds = computed(() => {
    if (!room.entryFee || room.entryFee <= 0) return false
    if (!userWallet || !userWallet.found) return false
    return userWallet.balance < room.entryFee
  })

  // El usuario no tiene billetera utilizable para pagar la entrada
  // (ni en la moneda requerida, ni una wallet activa para aplicar conversión)
  const walletNotFound = computed(() =>
    !!room.entryFee &&
    room.entryFee > 0 &&
    !!userWallet &&
    !userWallet.found
  )

  /** Punto de entrada del botón "Unirme". Muestra modal si hay costo; sino une directo. */
  function openJoinModal() {
    if (!canJoin.value) {
      joinError.value = 'Debes seleccionar un equipo para unirte.'
      return
    }
    if (room.entryFee && room.entryFee > 0) {
      showConfirmModal.value = true
    } else {
      doJoin()
    }
  }

  function closeConfirmModal() {
    showConfirmModal.value = false
  }

  /** Llamado desde el modal de confirmación tras confirmar el pago. */
  function confirmAndJoin() {
    showConfirmModal.value = false
    doJoin()
  }

  async function doJoin() {
    if (hasJoined.value || isLoading.value) return
    if (!canJoin.value) {
      joinError.value = 'Debes seleccionar un equipo para unirte.'
      return
    }

    isLoading.value = true
    joinError.value = null

    try {
      const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? ''
      const res  = await fetch(`/trivia-game/room/${room.id}/join`, {
        method:  'POST',
        headers: {
          'Content-Type':     'application/json',
          'Accept':           'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN':     csrf,
        },
        body: JSON.stringify({ team_id: selectedTeamId.value }),
      })

      const data = await res.json().catch(() => ({}))

      if (res.ok) {
        const player: Player = {
          id:       data.player?.id    ?? currentUserId,
          name:     data.player?.name  ?? authName.value,
          roomCode: room.code,
          teamId:   data.team_id       ?? selectedTeamId.value,
          joinedAt: new Date().toISOString(),
        }
        onJoined(player)
        hasJoined.value = true
      } else {
        joinError.value = data.message ?? ({
          402: 'Saldo insuficiente para unirte a esta sala.',
          403: 'No tienes permiso para unirte.',
          409: 'Ya estás registrado en esta sala.',
          422: 'La sala no está disponible.',
        } as Record<number, string>)[res.status] ?? 'Error al unirse a la sala.'
      }
    } catch {
      joinError.value = 'Error de conexión. Intenta de nuevo.'
    } finally {
      isLoading.value = false
    }
  }

  return {
    hasJoined, isLoading, joinError, showConfirmModal,
    canJoin, feeWithoutCurrency, hasInsufficientFunds, walletNotFound,
    openJoinModal, closeConfirmModal, confirmAndJoin, doJoin,
  }
}
