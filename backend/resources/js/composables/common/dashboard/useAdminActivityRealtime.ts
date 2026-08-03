import { ref, onMounted, onBeforeUnmount } from 'vue'
import echo from '@/js/plugins/echo'
import type { ActivityItem } from '@/js/types/common/dashboard/dashboard'

interface UserRegisteredPayload {
  user_id: string
  username: string
  email: string | null
  role_name: string
  created_at: string
}

interface CardSoldPayload {
  room_id: string
  room_name: string
  quantity: number
  amount_usd: number
}

interface WinnerDeclaredPayload {
  room_id: string
  winner: { id: string; name: string; card_id: string; pattern: string; prize: number }
  timestamp: string
}

interface WalletMovementProcessedPayload {
  movement_id: string
  movement_type: string
  status: 'COMPLETED' | 'REJECTED'
  pending_count: number
}

const ROLE_LABELS: Record<string, string> = {
  player: 'Jugador', commerce: 'Comercio', customer: 'Cliente', treasurer: 'Tesorero',
}

/**
 * Alimenta el feed de actividad reciente del dashboard de Admin/SuperAdmin
 * escuchando dos canales privados que ya existen en el sistema:
 *
 * - `admins.activity`: usuarios nuevos, cartones vendidos, ganadores de bingo.
 * - `admins.wallet-movements`: solo el desenlace (aprobado/rechazado) de un
 *   movimiento — la solicitud en sí ya la maneja `useWalletMovementsRealtime`
 *   para el widget de pendientes, este composable únicamente añade el cierre
 *   del ciclo al feed general para que quede visible ahí también.
 *
 * Ambos canales toleran múltiples suscriptores simultáneos (Echo cuenta
 * referencias), así que compartir `admins.wallet-movements` con el widget de
 * pendientes es seguro siempre que se usen `stopListening` y no `leave` al
 * desmontar — de lo contrario se cortaría la suscripción del otro consumidor.
 */
export function useAdminActivityRealtime(onActivity: (item: ActivityItem) => void) {
  const isLive = ref(false)
  const newUsersToday = ref(0)
  const cardsSoldToday = ref(0)
  const winnersToday = ref(0)

  const onUserRegistered = (payload: UserRegisteredPayload) => {
    newUsersToday.value++
    onActivity({
      id: `user-${payload.user_id}-${Date.now()}`,
      type: 'success',
      title: `Nuevo ${ROLE_LABELS[payload.role_name] ?? payload.role_name}`,
      description: `@${payload.username}${payload.email ? ` · ${payload.email}` : ''}`,
      timestamp: payload.created_at,
    })
  }

  const onCardSold = (payload: CardSoldPayload) => {
    cardsSoldToday.value += payload.quantity
    onActivity({
      id: `card-${payload.room_id}-${Date.now()}`,
      type: 'bingo',
      title: `${payload.quantity} cartón(es) vendido(s)`,
      description: `Sala ${payload.room_name} · $${payload.amount_usd.toFixed(2)}`,
      timestamp: new Date().toISOString(),
    })
  }

  const onWinnerDeclared = (payload: WinnerDeclaredPayload) => {
    winnersToday.value++
    onActivity({
      id: `winner-${payload.winner.card_id}-${Date.now()}`,
      type: 'bingo',
      title: `🏆 ${payload.winner.name} ganó`,
      description: `Patrón ${payload.winner.pattern} · $${payload.winner.prize.toFixed(2)}`,
      timestamp: payload.timestamp,
    })
  }

  const onMovementProcessed = (payload: WalletMovementProcessedPayload) => {
    onActivity({
      id: `movement-processed-${payload.movement_id}`,
      type: payload.status === 'COMPLETED' ? 'success' : 'danger',
      title: payload.status === 'COMPLETED' ? 'Movimiento aprobado' : 'Movimiento rechazado',
      description: `Tipo: ${payload.movement_type}`,
      timestamp: new Date().toISOString(),
    })
  }

  onMounted(() => {
    const activityChannel = echo.private('admins.activity')

    activityChannel.listen('.user.registered', onUserRegistered)
    activityChannel.listen('.card.sold', onCardSold)
    activityChannel.listen('.WinnerDeclared', onWinnerDeclared)

    activityChannel.subscribed(() => { isLive.value = true })
    activityChannel.error(() => { isLive.value = false })

    echo.private('admins.wallet-movements')
      .listen('.wallet.movement.processed', onMovementProcessed)
  })

  onBeforeUnmount(() => {
    echo.private('admins.activity')
      .stopListening('.user.registered', onUserRegistered)
      .stopListening('.card.sold', onCardSold)
      .stopListening('.WinnerDeclared', onWinnerDeclared)

    echo.private('admins.wallet-movements')
      .stopListening('.wallet.movement.processed', onMovementProcessed)
  })

  return { isLive, newUsersToday, cardsSoldToday, winnersToday }
}
