/**
 * Composable para la API de pagos USDT-TRC20 (App\Domains\Payments backend).
 * Ver PAYMENTS_USDT_TRC20_DESIGN.md.
 */

export interface PaymentOrder {
  id: string
  status: 'PENDING' | 'DETECTED' | 'CONFIRMING' | 'CONFIRMED' | 'UNDERPAID' | 'OVERPAID' | 'EXPIRED' | 'FAILED' | 'CANCELLED' | 'CREDITED'
  network: string
  token: string
  deposit_address: string
  expected_amount: number
  received_amount: number | null
  expires_at: string | null
  detected_at: string | null
  confirmed_at: string | null
  product?: { code: string; name: string }
}

export const usePaymentsApi = () => {
  const api = useApi()

  const createOrder = async (paymentProductCode: string) => {
    return api.post<{ data: PaymentOrder }>('/payments/orders', {
      payment_product_code: paymentProductCode,
    })
  }

  const getOrder = async (orderId: string) => {
    return api.get<{ data: PaymentOrder }>(`/payments/orders/${orderId}`)
  }

  const getOrderStatus = async (orderId: string) => {
    return api.get<{
      status: PaymentOrder['status']
      expected_amount: number
      received_amount: number | null
      expires_at: string | null
    }>(`/payments/orders/${orderId}/status`)
  }

  const qrUrl = (orderId: string): string => {
    const config = useRuntimeConfig()
    return `${config.public.apiBase}/payments/orders/${orderId}/qr`
  }

  return {
    createOrder,
    getOrder,
    getOrderStatus,
    qrUrl,
  }
}
