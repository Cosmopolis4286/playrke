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

const ROLE_LABELS: Record<string, string> = {
  player: 'Jugador', commerce: 'Comercio', customer: 'Cliente', treasurer: 'Tesorero',
}

/**
 * Escucha el canal privado `admins.activity` (nuevos usuarios + ventas de
 * cartones). Pensado para los dashboards de Admin y SuperAdmin, que comparten
 * el mismo contexto de visibilidad.
 *
 * Los movimientos de wallet pendientes se manejan aparte en
 * `useWalletMovementsRealtime` (canal `admins.wallet-movements`), que puede
 * compartir la suscripción con el badge del sidebar sin pisarla.
 *
 * Antepone los eventos recibidos al feed de actividad reciente.
 */
export function useAdminActivityRealtime(onActivity: (item: ActivityItem) => void) {
  const isLive = ref(false)
  const newUsersToday = ref(0)
  const cardsSoldToday = ref(0)

  onMounted(() => {
    const activityChannel = echo.private('admins.activity')

    activityChannel.listen('.user.registered', (payload: UserRegisteredPayload) => {
      newUsersToday.value++
      onActivity({
        id: `user-${payload.user_id}-${Date.now()}`,
        type: 'success',
        title: `Nuevo ${ROLE_LABELS[payload.role_name] ?? payload.role_name}`,
        description: `@${payload.username}${payload.email ? ` · ${payload.email}` : ''}`,
        timestamp: payload.created_at,
      })
    })

    activityChannel.listen('.card.sold', (payload: CardSoldPayload) => {
      cardsSoldToday.value += payload.quantity
      onActivity({
        id: `card-${payload.room_id}-${Date.now()}`,
        type: 'bingo',
        title: `${payload.quantity} cartón(es) vendido(s)`,
        description: `Sala ${payload.room_name} · $${payload.amount_usd.toFixed(2)}`,
        timestamp: new Date().toISOString(),
      })
    })

    activityChannel.subscribed(() => { isLive.value = true })
    activityChannel.error(() => { isLive.value = false })
  })

  onBeforeUnmount(() => {
    echo.leave('admins.activity')
  })

  return { isLive, newUsersToday, cardsSoldToday }
}
