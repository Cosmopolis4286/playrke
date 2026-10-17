<script setup lang="ts">
import { computed } from 'vue'
import type {
  SellerPaymentReview,
  SellerPrizesToDeliver,
  SellerPurchaseCodes,
  SellerRejectedPayment,
} from '@/js/types/roles/seller/dashboard/dashboard-seller'
import DashboardEmptyState from '@/js/Components/Common/Dashboard/DashboardEmptyState.vue'

const props = defineProps<{
  paymentReview?:   SellerPaymentReview
  prizesToDeliver?: SellerPrizesToDeliver
  purchaseCodes?:   SellerPurchaseCodes
}>()

const pendingCount = computed(() => props.paymentReview?.pending_count ?? 0)
const rejected     = computed(() => props.paymentReview?.rejected ?? [])
const prizeCount   = computed(() => props.prizesToDeliver?.count ?? 0)
const codes        = computed(() => props.purchaseCodes)

const isEmpty = computed(() =>
  pendingCount.value === 0 && rejected.value.length === 0 && prizeCount.value === 0 && !codes.value?.active,
)

function sinceLabel(iso: string | null): string | null {
  if (!iso) return null
  const minutes = Math.floor((Date.now() - new Date(iso).getTime()) / 60_000)
  if (minutes < 60) return `hace ${Math.max(minutes, 1)} min`
  if (minutes < 1440) return `hace ${Math.floor(minutes / 60)} h`
  return `hace ${Math.floor(minutes / 1440)} d`
}

// Enlace de WhatsApp para coordinar la devolución con el comprador.
function whatsappHref(row: SellerRejectedPayment): string | null {
  const digits = row.phone?.replace(/\D/g, '') ?? ''
  if (!digits) return null
  const text = `Hola ${row.buyer}, el pago de tus ${row.cards} cartón(es)${row.room ? ` de la sala ${row.room}` : ''} fue rechazado. Te devuelvo ${row.amount}.`
  return `https://wa.me/${digits}?text=${encodeURIComponent(text)}`
}
</script>

<template>
  <div v-if="!isEmpty" class="spa-list">

    <!-- Pagos en revisión -->
    <a v-if="pendingCount > 0" href="/bingo/cards-sold" class="spa-item spa-item--info">
      <span class="spa-item__icon"><i class="fas fa-hourglass-half"></i></span>
      <span class="spa-item__body">
        <span class="spa-item__label">Pagos en revisión</span>
        <span class="spa-item__hint">
          {{ paymentReview?.pending_cards }} cartón(es) que tus compradores aún no ven<template v-if="sinceLabel(paymentReview?.oldest_pending_at ?? null)"> · el más antiguo {{ sinceLabel(paymentReview?.oldest_pending_at ?? null) }}</template>
        </span>
      </span>
      <span class="spa-item__figures">
        <span class="spa-item__count">{{ pendingCount }}</span>
        <span v-if="paymentReview?.pending_amount" class="spa-item__amount">{{ paymentReview.pending_amount }}</span>
      </span>
    </a>

    <!-- Devoluciones: pagos rechazados -->
    <div v-if="rejected.length" class="spa-item spa-item--danger spa-item--stacked">
      <div class="spa-item__row">
        <span class="spa-item__icon"><i class="fas fa-rotate-left"></i></span>
        <span class="spa-item__body">
          <span class="spa-item__label">Devoluciones pendientes</span>
          <span class="spa-item__hint">Pagos rechazados en 14 días: los cartones se anularon, devuelve el dinero.</span>
        </span>
        <span class="spa-item__figures"><span class="spa-item__count">{{ rejected.length }}</span></span>
      </div>
      <ul class="spa-refunds">
        <li v-for="row in rejected" :key="row.id" class="spa-refund">
          <span class="spa-refund__who">
            {{ row.buyer }}
            <small>{{ row.cards }} cartón(es){{ row.room ? ` · ${row.room}` : '' }}</small>
          </span>
          <span class="spa-refund__amount">{{ row.amount }}</span>
          <a
            v-if="whatsappHref(row)"
            :href="whatsappHref(row)!"
            target="_blank"
            rel="noopener"
            class="spa-refund__wa"
            title="Escribir por WhatsApp"
          ><i class="fab fa-whatsapp"></i></a>
        </li>
      </ul>
    </div>

    <!-- Premios por entregar -->
    <a v-if="prizeCount > 0" href="/bingo/winners" class="spa-item spa-item--warning">
      <span class="spa-item__icon"><i class="fas fa-gift"></i></span>
      <span class="spa-item__body">
        <span class="spa-item__label">Premios por entregar</span>
        <span class="spa-item__hint">Ganados con cartones que vendiste; coordina la entrega con tu comercio.</span>
      </span>
      <span class="spa-item__figures">
        <span class="spa-item__count">{{ prizeCount }}</span>
        <span v-if="prizesToDeliver?.amount" class="spa-item__amount">{{ prizesToDeliver.amount }}</span>
      </span>
    </a>

    <!-- Códigos de compra -->
    <a v-if="codes?.active" href="/bingo-card/generate" class="spa-item spa-item--success">
      <span class="spa-item__icon"><i class="fas fa-key"></i></span>
      <span class="spa-item__body">
        <span class="spa-item__label">Códigos de compra vigentes</span>
        <span class="spa-item__hint">
          {{ codes.remaining_uses }} compra(s) disponibles<template v-if="codes.expiring_soon"> · {{ codes.expiring_soon }} vence(n) en 3 días</template>
        </span>
      </span>
      <span class="spa-item__figures"><span class="spa-item__count">{{ codes.active }}</span></span>
    </a>
  </div>
  <DashboardEmptyState
    v-else
    icon="fas fa-circle-check"
    title="Todo al día"
    description="No tienes pagos en revisión, devoluciones ni premios por entregar."
    compact
  />
</template>

<style scoped>
.spa-list { display: flex; flex-direction: column; gap: 0.5rem; }

.spa-item {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0.65rem 0.875rem;
  background: var(--ds-surface-2);
  border-radius: 10px;
  border-left: 3px solid transparent;
  text-decoration: none; color: inherit;
  transition: box-shadow 0.15s;
}
a.spa-item:hover { box-shadow: var(--ds-card-hover-shadow); text-decoration: none; }
.spa-item--stacked { flex-direction: column; align-items: stretch; gap: 0.5rem; }
.spa-item__row { display: flex; align-items: center; gap: 0.75rem; }
.spa-item--info    { border-left-color: #3b82f6; }
.spa-item--danger  { border-left-color: #ef4444; }
.spa-item--warning { border-left-color: #f59e0b; }
.spa-item--success { border-left-color: #10b981; }

.spa-item__icon {
  width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  background: var(--ds-surface); color: var(--ds-text-2); font-size: 0.85rem;
}
.spa-item--info .spa-item__icon    { color: #3b82f6; }
.spa-item--danger .spa-item__icon  { color: #ef4444; }
.spa-item--warning .spa-item__icon { color: #f59e0b; }
.spa-item--success .spa-item__icon { color: #10b981; }

.spa-item__body { display: flex; flex-direction: column; min-width: 0; flex: 1; }
.spa-item__label { font-size: 0.85rem; font-weight: 700; color: var(--ds-text); }
.spa-item__hint { font-size: 0.72rem; color: var(--ds-text-muted); }

.spa-item__figures { display: flex; flex-direction: column; align-items: flex-end; flex-shrink: 0; }
.spa-item__count { font-size: 1.05rem; font-weight: 800; color: var(--ds-text); line-height: 1.1; }
.spa-item__amount { font-size: 0.72rem; color: var(--ds-text-2); white-space: nowrap; }

.spa-refunds { list-style: none; margin: 0; padding: 0 0 0 calc(32px + 0.75rem); display: flex; flex-direction: column; gap: 0.35rem; }
.spa-refund { display: flex; align-items: center; gap: 0.5rem; font-size: 0.78rem; }
.spa-refund__who { flex: 1; min-width: 0; color: var(--ds-text); display: flex; flex-direction: column; }
.spa-refund__who small { font-size: 0.68rem; color: var(--ds-text-muted); }
.spa-refund__amount { font-weight: 700; color: var(--ds-text); white-space: nowrap; }
.spa-refund__wa { color: #16a34a; font-size: 1rem; }
.spa-refund__wa:hover { color: #15803d; }
</style>
