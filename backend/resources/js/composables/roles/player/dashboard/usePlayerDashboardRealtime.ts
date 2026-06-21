import { ref, onMounted, onBeforeUnmount } from 'vue'
import { usePage } from '@inertiajs/vue3'
import echo from '@/js/plugins/echo'

interface WalletUpdatedPayload {
  wallet_id: string
  balance: number
  currency_symbol: string
  movement_type: string
  amount: number
  movement_status: string
  movement_id: string
}

/**
 * Escucha el canal privado del jugador (App.Models.User.{id}) y expone
 * el saldo de billetera actualizado en tiempo real, junto con el estado
 * de la conexión, para que el dashboard del Player no dependa de recargar
 * la página tras un depósito, retiro, compra de cartón o premio.
 */
export function usePlayerDashboardRealtime(initialBalance: string | number) {
  const liveBalance = ref<string | number>(initialBalance)
  const isLive = ref(false)
  const lastUpdateAt = ref<Date | null>(null)

  const page = usePage()

  onMounted(() => {
    const userId = (page.props.auth as any)?.user?.id
    if (!userId) return

    const channel = echo.private(`App.Models.User.${userId}`)

    channel.listen('.wallet.updated', (payload: WalletUpdatedPayload) => {
      liveBalance.value = `${payload.currency_symbol}${payload.balance.toFixed(2)}`
      lastUpdateAt.value = new Date()
    })

    channel.subscribed(() => { isLive.value = true })
    channel.error(() => { isLive.value = false })
  })

  onBeforeUnmount(() => {
    const userId = (page.props.auth as any)?.user?.id
    if (userId) echo.leave(`App.Models.User.${userId}`)
  })

  return { liveBalance, isLive, lastUpdateAt }
}
