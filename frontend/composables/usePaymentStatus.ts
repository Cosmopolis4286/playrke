/**
 * Sigue el estado de una PaymentOrder en tiempo real vía WebSocket (Reverb,
 * canal privado App.Models.User.{id}, evento payment-order.updated) con
 * fallback a polling si el WS no llega a conectar o se cae. Ver
 * PAYMENTS_USDT_TRC20_DESIGN.md sección 3/8.
 */
import type { PaymentOrder } from '~/composables/usePaymentsApi'

export const usePaymentStatus = (orderId: string) => {
  const { user } = useAuth()
  const { connect, listenToPrivateChannel, isConnected } = useWebSocket()
  const { getOrderStatus } = usePaymentsApi()

  const order = ref<Partial<PaymentOrder>>({})
  const isTerminal = computed(() =>
    ['EXPIRED', 'FAILED', 'CANCELLED', 'CREDITED'].includes(order.value.status ?? '')
  )

  let pollTimer: ReturnType<typeof setInterval> | null = null
  const POLL_INTERVAL_MS = 5000

  const applyUpdate = (data: Partial<PaymentOrder>) => {
    order.value = { ...order.value, ...data }
    if (isTerminal.value) {
      stopPolling()
    }
  }

  const startPolling = () => {
    if (pollTimer) return
    pollTimer = setInterval(async () => {
      if (isTerminal.value) {
        stopPolling()
        return
      }
      try {
        const status = await getOrderStatus(orderId)
        applyUpdate(status)
      } catch (e) {
        console.error('[usePaymentStatus] polling error', e)
      }
    }, POLL_INTERVAL_MS)
  }

  const stopPolling = () => {
    if (pollTimer) {
      clearInterval(pollTimer)
      pollTimer = null
    }
  }

  const start = async () => {
    // Polling siempre corre como red de seguridad, incluso con WS
    // conectado — evita que un evento perdido deje la UI congelada
    // (el propio backend documenta que no hay webhook de terceros
    // confiable, así que la UI tampoco debe depender de un solo canal).
    startPolling()

    if (!user.value) return

    try {
      await connect()
      listenToPrivateChannel(
        `App.Models.User.${user.value.id}`,
        '.payment-order.updated',
        (data: any) => {
          if (data.id === orderId) {
            applyUpdate(data)
          }
        }
      )
    } catch (e) {
      console.error('[usePaymentStatus] WebSocket connect failed, relying on polling', e)
    }
  }

  onUnmounted(() => {
    stopPolling()
  })

  return {
    order,
    isConnected,
    isTerminal,
    start,
  }
}
