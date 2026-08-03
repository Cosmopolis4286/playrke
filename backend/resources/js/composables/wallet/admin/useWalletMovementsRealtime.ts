import { onBeforeUnmount, onMounted, ref } from 'vue'
import echo from '@js/plugins/echo'

export interface WalletMovementRequestedPayload {
  movement_id: string
  user_id: string
  user_name: string
  movement_type: string
  amount: number
  currency_symbol: string
  currency_iso: string
  url: string
}

export interface WalletMovementProcessedPayload {
  movement_id: string
  movement_type: string
  status: 'COMPLETED' | 'REJECTED'
  pending_count: number
}

/**
 * Único punto de suscripción al canal privado `admins.wallet-movements`.
 * Laravel Echo deja unirse al mismo canal varias veces desde distintos
 * componentes (cuenta referencias internamente), así que cada consumidor de
 * este composable puede montarse/desmontarse de forma independiente sin
 * pisar los listeners de los demás.
 *
 * `pendingCount` es la única fuente de verdad reactiva del contador de
 * pendientes en el cliente: arranca en `initialPendingCount` (calculado en
 * el servidor al renderizar la página) y se mantiene sincronizado en vivo
 * incrementando con `.wallet.movement.requested` y fijándose al valor
 * autoritativo que trae `.wallet.movement.processed` (evita drift si dos
 * pestañas/admins procesan movimientos a la vez).
 */
export function useWalletMovementsRealtime(
  initialPendingCount = 0,
  onRequested?: (payload: WalletMovementRequestedPayload) => void,
  onProcessed?: (payload: WalletMovementProcessedPayload) => void,
) {
  const pendingCount = ref(initialPendingCount)
  const isLive = ref(false)

  const requestedHandler = (payload: WalletMovementRequestedPayload) => {
    pendingCount.value++
    onRequested?.(payload)
  }
  const processedHandler = (payload: WalletMovementProcessedPayload) => {
    pendingCount.value = payload.pending_count
    onProcessed?.(payload)
  }

  onMounted(() => {
    const channel = echo.private('admins.wallet-movements')

    channel.listen('.wallet.movement.requested', requestedHandler)
    channel.listen('.wallet.movement.processed', processedHandler)

    channel.subscribed(() => { isLive.value = true })
    channel.error(() => { isLive.value = false })
  })

  // stopListening (no `echo.leave`): el badge global del layout y esta misma
  // página pueden estar suscritos al canal a la vez — `leave` cerraría la
  // suscripción compartida para el otro consumidor. Solo se retiran los
  // callbacks propios de esta instancia.
  onBeforeUnmount(() => {
    echo.private('admins.wallet-movements')
      .stopListening('.wallet.movement.requested', requestedHandler)
      .stopListening('.wallet.movement.processed', processedHandler)
  })

  return { pendingCount, isLive }
}
