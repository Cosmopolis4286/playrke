<script setup lang="ts">
/**
 * Estado de una orden de pago USDT-TRC20. Ver PAYMENTS_USDT_TRC20_DESIGN.md
 * secciones 3 y 5 — QR + dirección + monto, actualización en vivo por
 * Reverb con polling de fallback (usePaymentOrder).
 */
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { usePaymentOrder } from '@js/composables/payments/usePaymentOrder'
import type { PaymentOrder } from '@js/types/payments/payment-order'

const props = defineProps<{ order: PaymentOrder }>()

const page = usePage<{ auth: { user: { id: string | number } } }>()
const { order, error } = usePaymentOrder(props.order, page.props.auth.user.id)

const statusLabel: Record<string, string> = {
  PENDING: 'Esperando pago',
  DETECTED: 'Pago detectado, esperando confirmaciones',
  CONFIRMING: 'Confirmando en la red',
  CONFIRMED: 'Confirmado, acreditando...',
  UNDERPAID: 'Monto insuficiente recibido',
  OVERPAID: 'Monto excede lo esperado — en revisión',
  EXPIRED: 'Orden expirada',
  FAILED: 'Pago fallido',
  CANCELLED: 'Orden cancelada',
  CREDITED: '¡Compra completada!',
}

const isFinal = computed(() => ['CREDITED'].includes(order.value.status))
const isProblem = computed(() => ['UNDERPAID', 'OVERPAID', 'EXPIRED', 'FAILED', 'CANCELLED'].includes(order.value.status))

const qrUrl = computed(() => `/payments/orders/${order.value.id}/qr`)
</script>

<template>
  <div class="payment-page">
    <div class="payment-card">
      <h1 class="payment-title">Pago con USDT (TRC20)</h1>
      <p v-if="order.product" class="payment-product">{{ order.product.name }}</p>

      <div class="payment-status" :class="{ 'payment-status--ok': isFinal, 'payment-status--problem': isProblem }">
        {{ statusLabel[order.status] ?? order.status }}
      </div>

      <template v-if="!isFinal && !isProblem">
        <img :src="qrUrl" alt="QR de la dirección de depósito" class="payment-qr" />

        <div class="payment-detail">
          <label>Dirección de depósito (red TRON)</label>
          <code class="payment-address">{{ order.deposit_address }}</code>
        </div>

        <div class="payment-detail">
          <label>Monto exacto a enviar</label>
          <strong class="payment-amount">{{ order.expected_amount }} USDT</strong>
        </div>

        <p class="payment-warning">
          Envía únicamente USDT sobre la red TRON (TRC20) a esta dirección, por el monto exacto.
          Otros montos, redes o tokens no serán acreditados automáticamente.
        </p>
      </template>

      <div v-if="order.received_amount" class="payment-detail">
        <label>Monto recibido</label>
        <strong>{{ order.received_amount }} USDT</strong>
      </div>

      <p v-if="error" class="payment-error">{{ error }}</p>
    </div>
  </div>
</template>

<style scoped>
.payment-page { max-width: 480px; margin: 2rem auto; padding: 0 1rem; }
.payment-card {
  border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.5rem;
  display: flex; flex-direction: column; align-items: center; gap: 1rem; background: #fff;
}
.payment-title { font-size: 1.25rem; font-weight: 700; margin: 0; }
.payment-product { color: #6b7280; margin: 0; }

.payment-status {
  padding: 0.5rem 1rem; border-radius: 999px; font-size: 0.875rem; font-weight: 600;
  background: #eef2ff; color: #4338ca;
}
.payment-status--ok { background: #dcfce7; color: #166534; }
.payment-status--problem { background: #fee2e2; color: #991b1b; }

.payment-qr { width: 220px; height: 220px; border-radius: 0.5rem; border: 1px solid #e5e7eb; }

.payment-detail { width: 100%; display: flex; flex-direction: column; gap: 0.25rem; text-align: center; }
.payment-detail label { font-size: 0.75rem; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em; }
.payment-address { font-size: 0.85rem; word-break: break-all; background: #f9fafb; padding: 0.5rem; border-radius: 0.4rem; }
.payment-amount { font-size: 1.25rem; color: #111827; }

.payment-warning { font-size: 0.8rem; color: #9ca3af; text-align: center; margin: 0; }
.payment-error { color: #991b1b; font-size: 0.85rem; }
</style>
