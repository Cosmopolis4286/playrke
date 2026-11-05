<script setup lang="ts">
import type { PendingReceivable, ReconciliationAlert } from '@/js/composables/roles/superadmin/dashboard/useReconciliation'

defineProps<{
  receivables: PendingReceivable[]
  alerts: ReconciliationAlert[]
  alertsOpen: number
  loading: boolean
  error: string | null
  busyKey: string | null
  formatUsd: (v: number, decimals?: number) => string
  formatDateTime: (iso: string) => string
}>()

const emit = defineEmits<{
  refresh: []
  settle: [r: PendingReceivable]
  resolve: [a: ReconciliationAlert]
}>()

const ALERT_LABELS: Record<string, string> = {
  RKE_REVERSAL_FAILED: 'RKE sin devolver',
  JACKPOT_REVERSAL_SHORTFALL: 'Jackpot sin devolver',
  CREATOR_PAYOUT_FAILED: 'Pago al creador fallido',
  LEGACY_LOT_NOT_REVERSED: 'Reembolso sin desglose',
  EXTERNAL_PLAYER_REFUND_PENDING: 'Devolución externa pendiente'
}
</script>

<template>
  <div class="rc-section">
    <div class="rc-header">
      <div class="rc-header__title">
        <div class="rc-header__icon"><i class="fas fa-scale-balanced" /></div>
        <div>
          <h2 class="rc-header__h2">
            Conciliación
            <span v-if="alertsOpen" class="rc-badge" :title="`${alertsOpen} alerta(s) abierta(s)`">{{ alertsOpen }}</span>
          </h2>
          <p class="rc-header__sub">RKE que deben los comercios por ventas fuera del sistema y movimientos de dinero pendientes de conciliar</p>
        </div>
      </div>
      <button
        class="rc-refresh"
        :class="{ 'rc-refresh--spin': loading }"
        :disabled="loading"
        @click="emit('refresh')"
      >
        <i class="fas fa-sync-alt" />
      </button>
    </div>

    <div v-if="error" class="rc-state rc-state--error"><i class="fas fa-exclamation-triangle" /> {{ error }}</div>

    <template v-else>
      <!-- Alertas abiertas: primero, son las que requieren acción -->
      <div v-if="alerts.length" class="rc-block">
        <p class="rc-block__title rc-block__title--alert"><i class="fas fa-triangle-exclamation" /> Alertas abiertas</p>
        <ul class="rc-list">
          <li v-for="a in alerts" :key="a.id" class="rc-item rc-item--alert">
            <div class="rc-item__info">
              <p class="rc-item__label">{{ ALERT_LABELS[a.type] ?? a.type }}</p>
              <p class="rc-item__desc">{{ a.message }}</p>
              <p class="rc-item__date">{{ formatDateTime(a.created_at) }}</p>
            </div>
            <div class="rc-item__side">
              <p class="rc-item__amount">{{ a.symbol ?? (a.currency ?? '$') }} {{ formatUsd(a.amount) }}</p>
              <button
                class="rc-btn rc-btn--ghost"
                :disabled="busyKey === a.id"
                @click="emit('resolve', a)"
              >
                <i class="fas fa-check" /> Resuelta
              </button>
            </div>
          </li>
        </ul>
      </div>

      <!-- Cuentas por cobrar -->
      <div class="rc-block">
        <p class="rc-block__title"><i class="fas fa-file-invoice-dollar" /> Por cobrar a comercios (ventas fuera de la billetera)</p>
        <p class="rc-block__hint">
          Lo que cada comercio retuvo en efectivo y corresponde a la plataforma: RKE, bote (la plataforma paga los
          premios) y jackpot. Las comisiones de vendedor, agencia, comercializadora y creador se las queda la red.
        </p>

        <div v-if="loading && !receivables.length" class="rc-state"><span class="rc-spinner" /> Cargando…</div>
        <div v-else-if="!receivables.length" class="rc-state rc-state--empty"><i class="fas fa-circle-check" /> Sin deudas pendientes.</div>

        <ul v-else class="rc-list">
          <li v-for="r in receivables" :key="`${r.debtor_user_id}:${r.currency_id}`" class="rc-item">
            <div class="rc-item__info">
              <p class="rc-item__label">{{ r.debtor }}</p>
              <p class="rc-item__desc">{{ r.sales }} venta(s) · desde {{ formatDateTime(r.oldest) }}</p>
              <p class="rc-item__desc">
                RKE {{ formatUsd(r.rke) }} · Bote {{ formatUsd(r.prize_pool) }} · Jackpot {{ formatUsd(r.jackpot) }}
              </p>
            </div>
            <div class="rc-item__side">
              <p class="rc-item__amount">{{ r.symbol }} {{ formatUsd(r.total) }} <span class="rc-item__cur">{{ r.currency }}</span></p>
              <button
                class="rc-btn"
                :disabled="busyKey === `${r.debtor_user_id}:${r.currency_id}`"
                @click="emit('settle', r)"
              >
                <i class="fas fa-hand-holding-dollar" /> Marcar cobrado
              </button>
            </div>
          </li>
        </ul>
      </div>
    </template>
  </div>
</template>

<style scoped>
.rc-section {
  background: var(--ds-surface);
  border: 1px solid var(--ds-border);
  border-radius: 14px;
  box-shadow: 0 2px 12px rgba(102, 126, 234, 0.08);
  padding: 1.5rem;
  margin-top: 0.25rem;
}
.rc-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 1.25rem; }
.rc-header__title { display: flex; align-items: center; gap: 0.875rem; }
.rc-header__icon {
  width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0;
  background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff;
  display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
}
.rc-header__h2 { font-size: 1.05rem; font-weight: 700; color: var(--ds-text); margin: 0 0 2px; display: flex; align-items: center; gap: 8px; }
.rc-header__sub { font-size: 0.78rem; color: var(--ds-text-2); margin: 0; }
.rc-badge { background: #dc2626; color: #fff; font-size: 0.7rem; font-weight: 700; border-radius: 10px; padding: 1px 8px; }
.rc-refresh {
  display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; flex-shrink: 0;
  border-radius: 8px; border: 1px solid var(--ds-border-2); background: var(--ds-surface-2); color: #d97706; cursor: pointer;
}
.rc-refresh:disabled { opacity: 0.5; cursor: not-allowed; }
.rc-refresh--spin i { animation: rc-spin 0.7s linear infinite; }
@keyframes rc-spin { to { transform: rotate(360deg); } }

.rc-block + .rc-block { margin-top: 1.25rem; }
.rc-block__title { font-size: 0.875rem; font-weight: 700; color: var(--ds-text); display: flex; align-items: center; gap: 6px; margin: 0 0 0.75rem; }
.rc-block__title i { color: #d97706; }
.rc-block__title--alert i { color: #dc2626; }
.rc-block__hint { font-size: 0.75rem; color: var(--ds-text-2); margin: -0.4rem 0 0.75rem; }

.rc-list { list-style: none; margin: 0; padding: 0; border: 1px solid var(--ds-border); border-radius: 10px; overflow: hidden; }
.rc-item { display: flex; align-items: center; gap: 12px; padding: 0.75rem 1rem; border-bottom: 1px solid var(--ds-border); flex-wrap: wrap; }
.rc-item:last-child { border-bottom: none; }
.rc-item--alert { border-left: 3px solid #dc2626; }
.rc-item__info { flex: 1; min-width: 200px; }
.rc-item__label { font-size: 0.82rem; font-weight: 700; color: var(--ds-text); margin: 0 0 1px; }
.rc-item__desc { font-size: 0.75rem; color: var(--ds-text-2); margin: 0 0 1px; }
.rc-item__date { font-size: 0.7rem; color: var(--ds-text-muted); margin: 0; }
.rc-item__side { text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 6px; }
.rc-item__amount { font-size: 0.9rem; font-weight: 700; color: var(--ds-text); margin: 0; }
.rc-item__cur { font-size: 0.7rem; color: var(--ds-text-muted); font-weight: 600; }

.rc-btn {
  display: inline-flex; align-items: center; gap: 5px; padding: 5px 12px; border-radius: 8px; border: none;
  font-size: 0.75rem; font-weight: 600; cursor: pointer; background: #d97706; color: #fff;
}
.rc-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.rc-btn--ghost { background: var(--ds-surface-2); color: var(--ds-text); border: 1px solid var(--ds-border-2); }

.rc-state { display: flex; align-items: center; justify-content: center; gap: 0.75rem; padding: 1.5rem; color: var(--ds-text-2); font-size: 0.85rem; }
.rc-state--error { color: #ef4444; }
.rc-state--empty i { color: #16a34a; }
.rc-spinner { width: 18px; height: 18px; border: 3px solid var(--ds-border-2); border-top-color: #d97706; border-radius: 50%; animation: rc-spin 0.8s linear infinite; }
</style>
