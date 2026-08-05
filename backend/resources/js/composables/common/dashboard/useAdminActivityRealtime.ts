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

interface WinEventPayload {
  id: string
  game: string
  winner_name: string
  prize_kind: 'money' | 'item' | 'service'
  prize_label: string | null
  amount: number | null
  currency_symbol: string
  is_usd: boolean
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
 * Qué tipos de evento ve cada rol en el feed general de actividad. Config
 * simple en código (no hay UI de administración para esto — con 2 roles y
 * 4 tipos de evento no se justifica una tabla de visibilidad ni una página
 * de gestión dedicada; si crece, migrar a un patrón tipo SidebarMenuVisibility).
 *
 * Admin no ve el desenlace de movimientos de wallet en el feed general —
 * sigue teniendo su propio widget de pendientes (DashboardPendingWalletWidget),
 * pero el feed general es sobre usuarios/ventas/ganadores.
 */
const WALLET_EVENTS_VISIBLE_FOR: Array<'admin' | 'superadmin'> = ['superadmin']

/**
 * Alimenta el feed de actividad reciente del dashboard de Admin/SuperAdmin.
 * Escucha solo señales de alto valor, sin duplicados:
 *
 * - `admins.activity` (privado): usuarios nuevos, cartones vendidos.
 * - `platform.win-events` (público): ganadores de CUALQUIER juego (bingo,
 *   trivia, futuros). Se usa este evento agregador en vez de escuchar el
 *   `.WinnerDeclared` específico de bingo porque ambos se disparan para el
 *   mismo gane (BingoDrawService dispara los dos secuencialmente) — escuchar
 *   ambos duplicaría la notificación en el feed.
 * - `admins.wallet-movements` (privado, solo SuperAdmin): solo el desenlace
 *   (aprobado/rechazado) de un movimiento — la solicitud en sí ya la maneja
 *   `useWalletMovementsRealtime` para el widget de pendientes, este
 *   composable añade el cierre del ciclo al feed general.
 *
 * Los canales privados toleran múltiples suscriptores simultáneos (Echo
 * cuenta referencias), así que compartir `admins.wallet-movements` con el
 * widget de pendientes es seguro siempre que se use `stopListening` y no
 * `leave` al desmontar — de lo contrario se cortaría la suscripción del
 * otro consumidor.
 */
export function useAdminActivityRealtime(onActivity: (item: ActivityItem) => void, role: 'admin' | 'superadmin' = 'admin') {
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

  const onWinEvent = (payload: WinEventPayload) => {
    winnersToday.value++
    const prize = payload.prize_kind === 'money' && payload.amount !== null
      ? `${payload.currency_symbol}${payload.amount.toFixed(2)}`
      : (payload.prize_label ?? 'Premio')
    onActivity({
      id: `win-${payload.id}`,
      type: 'bingo',
      title: `🏆 ${payload.winner_name} ganó en ${payload.game}`,
      description: prize,
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

  const showWalletEvents = WALLET_EVENTS_VISIBLE_FOR.includes(role)

  onMounted(() => {
    const activityChannel = echo.private('admins.activity')

    activityChannel.listen('.user.registered', onUserRegistered)
    activityChannel.listen('.card.sold', onCardSold)

    activityChannel.subscribed(() => { isLive.value = true })
    activityChannel.error(() => { isLive.value = false })

    echo.channel('platform.win-events')
      .listen('.NewWin', onWinEvent)

    if (showWalletEvents) {
      echo.private('admins.wallet-movements')
        .listen('.wallet.movement.processed', onMovementProcessed)
    }
  })

  onBeforeUnmount(() => {
    echo.private('admins.activity')
      .stopListening('.user.registered', onUserRegistered)
      .stopListening('.card.sold', onCardSold)

    echo.channel('platform.win-events')
      .stopListening('.NewWin', onWinEvent)

    if (showWalletEvents) {
      echo.private('admins.wallet-movements')
        .stopListening('.wallet.movement.processed', onMovementProcessed)
    }
  })

  return { isLive, newUsersToday, cardsSoldToday, winnersToday }
}
