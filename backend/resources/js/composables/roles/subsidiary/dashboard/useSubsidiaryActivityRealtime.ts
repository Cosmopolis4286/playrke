import { ref, onMounted, onBeforeUnmount } from 'vue'
import { usePage } from '@inertiajs/vue3'
import echo from '@/js/plugins/echo'
import type { ActivityItem } from '@/js/types/common/dashboard/dashboard'

interface CardSoldPayload {
  room_id: string
  room_name: string
  quantity: number
  amount_usd: number
}

/**
 * Escucha el canal privado `subsidiary.activity.{userId}` para reflejar en
 * vivo las ventas de cartones de LOS VENDEDORES de esta agencia — distinto
 * de useCreatorSalesRealtime.ts (creators.activity.{userId}), que solo
 * notifica a quien crea la sala; una Subsidiary nunca crea salas.
 */
export function useSubsidiaryActivityRealtime(onActivity: (item: ActivityItem) => void) {
  const isLive = ref(false)
  const cardsSoldToday = ref(0)
  const amountSoldToday = ref(0)

  const page = usePage()

  onMounted(() => {
    const userId = (page.props.auth as any)?.user?.id
    if (!userId) return

    const channel = echo.private(`subsidiary.activity.${userId}`)

    channel.listen('.card.sold', (payload: CardSoldPayload) => {
      cardsSoldToday.value += payload.quantity
      amountSoldToday.value += payload.amount_usd

      onActivity({
        id: `card-${payload.room_id}-${Date.now()}`,
        type: 'sale',
        title: `${payload.quantity} cartón(es) vendido(s)`,
        description: `Sala ${payload.room_name} · $${payload.amount_usd.toFixed(2)}`,
        timestamp: new Date().toISOString(),
      })
    })

    channel.subscribed(() => { isLive.value = true })
    channel.error(() => { isLive.value = false })
  })

  onBeforeUnmount(() => {
    const userId = (page.props.auth as any)?.user?.id
    if (userId) echo.leave(`subsidiary.activity.${userId}`)
  })

  return { isLive, cardsSoldToday, amountSoldToday }
}
