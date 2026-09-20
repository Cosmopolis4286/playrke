import { onBeforeUnmount, onMounted, ref } from 'vue'
import echo from '@js/plugins/echo'
import backendClient from '@js/plugins/axios'

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

export interface WalletMovementAssignedPayload {
  movement_id: string
  assigned_to_user_id: string | null
  assigned_to_name: string | null
}

export interface WalletMovementCriticalBacklogPayload {
  critical_count: number
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
 *
 * Resync activo (GET /sidebar/pending-wallet-movements-count): el broadcast
 * de arriba es best-effort (WalletService::broadcastAdminWalletMovement()
 * lo envuelve en try/catch y solo loguea si Reverb no responde) — si el
 * evento se pierde, `pendingCount` queda desalineado del real hasta un full
 * reload. Se resincroniza (a) al montar, por si `initialPendingCount` ya
 * llegó desactualizado del layout Blade, y (b) cada vez que el canal pasa de
 * error a suscrito, por si se perdieron eventos mientras estuvo caído.
 */
export function useWalletMovementsRealtime(
  initialPendingCount = 0,
  onRequested?: (payload: WalletMovementRequestedPayload) => void,
  onProcessed?: (payload: WalletMovementProcessedPayload) => void,
  onAssigned?: (payload: WalletMovementAssignedPayload) => void,
  onCriticalBacklog?: (payload: WalletMovementCriticalBacklogPayload) => void,
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
  const assignedHandler = (payload: WalletMovementAssignedPayload) => {
    onAssigned?.(payload)
  }
  const criticalBacklogHandler = (payload: WalletMovementCriticalBacklogPayload) => {
    onCriticalBacklog?.(payload)
  }

  async function resyncPendingCount() {
    try {
      const { data } = await backendClient.get('/sidebar/pending-wallet-movements-count')
      if (data.success) pendingCount.value = data.count
    } catch {
      // silencioso — mantiene el último valor conocido, ya sea el inicial
      // del layout o el último actualizado en vivo
    }
  }

  onMounted(() => {
    const channel = echo.private('admins.wallet-movements')

    channel.listen('.wallet.movement.requested', requestedHandler)
    channel.listen('.wallet.movement.processed', processedHandler)
    channel.listen('.wallet.movement.assigned', assignedHandler)
    channel.listen('.wallet.movement.critical-backlog', criticalBacklogHandler)

    let wasLive = false
    channel.subscribed(() => {
      isLive.value = true
      // Solo resincroniza en RE-conexión (wasLive ya true antes), no en la
      // primera — la primera se cubre con el resync explícito de abajo.
      if (wasLive) resyncPendingCount()
      wasLive = true
    })
    channel.error(() => { isLive.value = false })

    resyncPendingCount()
  })

  // stopListening (no `echo.leave`): el badge global del layout y esta misma
  // página pueden estar suscritos al canal a la vez — `leave` cerraría la
  // suscripción compartida para el otro consumidor. Solo se retiran los
  // callbacks propios de esta instancia.
  onBeforeUnmount(() => {
    echo.private('admins.wallet-movements')
      .stopListening('.wallet.movement.requested', requestedHandler)
      .stopListening('.wallet.movement.processed', processedHandler)
      .stopListening('.wallet.movement.assigned', assignedHandler)
      .stopListening('.wallet.movement.critical-backlog', criticalBacklogHandler)
  })

  return { pendingCount, isLive }
}
