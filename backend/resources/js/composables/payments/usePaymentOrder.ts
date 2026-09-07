import { onBeforeUnmount, ref } from 'vue'
import axios from 'axios'
import echo from '@js/plugins/echo'
import type { PaymentOrder } from '@js/types/payments/payment-order'

const TERMINAL_STATUSES = ['EXPIRED', 'FAILED', 'CANCELLED', 'CREDITED']

/**
 * Estado en vivo de una PaymentOrder — WebSocket (Reverb) como vía primaria,
 * con polling de fallback cada 5s en caso de que la conexión no esté
 * disponible. Ver PAYMENTS_USDT_TRC20_DESIGN.md sección 5.
 */
export function usePaymentOrder(initial: PaymentOrder, userId: string | number) {
  const order = ref<PaymentOrder>(initial)
  const error = ref<string | null>(null)

  let pollTimer: ReturnType<typeof setInterval> | null = null
  const channelName = `App.Models.User.${userId}`

  function isTerminal(status: string): boolean {
    return TERMINAL_STATUSES.includes(status)
  }

  async function refresh() {
    try {
      const { data } = await axios.get(`/payments/orders/${order.value.id}/show`)
      order.value = { ...order.value, ...data }
      if (isTerminal(order.value.status)) stopPolling()
    } catch {
      error.value = 'No se pudo actualizar el estado del pago.'
    }
  }

  function startPolling() {
    if (pollTimer || isTerminal(order.value.status)) return
    pollTimer = setInterval(refresh, 5000)
  }

  function stopPolling() {
    if (pollTimer) {
      clearInterval(pollTimer)
      pollTimer = null
    }
  }

  const channel = echo.private(channelName)
  channel.listen('.payment-order.updated', (payload: Partial<PaymentOrder> & { id: string }) => {
    if (payload.id !== order.value.id) return
    order.value = { ...order.value, ...payload }
    if (isTerminal(order.value.status)) stopPolling()
  })

  // Polling de fallback siempre activo mientras la orden no sea terminal —
  // no depende de detectar si el WS falló, simplemente corre en paralelo
  // como red de seguridad (barato: un GET liviano cada 5s).
  startPolling()

  onBeforeUnmount(() => {
    stopPolling()
    echo.leave(channelName)
  })

  return { order, error, refresh }
}
