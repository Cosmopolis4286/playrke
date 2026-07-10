<script setup lang="ts">
import type { KycState, WithdrawalLimitUsage } from '@js/types/kyc'

defineProps<{
  state: KycState | null
  limits: WithdrawalLimitUsage | null
  currencySymbol?: string
}>()

const emit = defineEmits<{ (e: 'verify'): void }>()

function pct(used: number, max: number) {
  return max > 0 ? Math.min(100, Math.round((used / max) * 100)) : 0
}
function fmt(n: number, symbol = '') {
  return symbol + ' ' + n.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>

<template>
  <div v-if="state" class="kyc-banner-wrap">

    <!-- Estado KYC -->
    <div
      class="kyc-status-bar"
      :class="{
        'kyc-status-bar--none':     state.kyc_status === 'NONE',
        'kyc-status-bar--pending':  state.kyc_status === 'PENDING',
        'kyc-status-bar--verified': state.kyc_status === 'VERIFIED',
        'kyc-status-bar--rejected': state.kyc_status === 'REJECTED',
      }"
    >
      <div class="kyc-status-bar__left">
        <i :class="[
          'fas kyc-status-bar__icon',
          state.kyc_status === 'VERIFIED' ? 'fa-shield-check' :
          state.kyc_status === 'PENDING'  ? 'fa-clock'        :
          state.kyc_status === 'REJECTED' ? 'fa-times-circle' : 'fa-shield-alt'
        ]" />
        <div>
          <p class="kyc-status-bar__label">
            Nivel {{ state.kyc_level }} —
            {{ state.kyc_status === 'VERIFIED' ? 'Identidad verificada' :
               state.kyc_status === 'PENDING'  ? 'Verificación en revisión (24-48 h)' :
               state.kyc_status === 'REJECTED' ? 'Verificación rechazada' :
               'Sin verificar' }}
          </p>
          <p v-if="state.kyc_status === 'REJECTED' && state.rejection_reason" class="kyc-status-bar__reason">
            {{ state.rejection_reason }}
          </p>
        </div>
      </div>
      <button
        v-if="state.kyc_status !== 'VERIFIED' && state.kyc_status !== 'PENDING'"
        type="button"
        class="kyc-status-bar__btn"
        @click="emit('verify')"
      >
        <i class="fas fa-id-card" /> Verificar
      </button>
    </div>

    <!-- Límites de retiro -->
    <div v-if="limits" class="kyc-limits">
      <p class="kyc-limits__title"><i class="fas fa-chart-bar" /> Límites de retiro</p>
      <div class="kyc-limits__grid">
        <div class="kyc-limit-item">
          <div class="kyc-limit-item__header">
            <span>Diario</span>
            <span>{{ fmt(limits.daily.used, currencySymbol) }} / {{ fmt(limits.daily.max, currencySymbol) }}</span>
          </div>
          <div class="kyc-limit-bar">
            <div class="kyc-limit-bar__fill" :style="{ width: pct(limits.daily.used, limits.daily.max) + '%' }" />
          </div>
        </div>
        <div class="kyc-limit-item">
          <div class="kyc-limit-item__header">
            <span>Semanal</span>
            <span>{{ fmt(limits.weekly.used, currencySymbol) }} / {{ fmt(limits.weekly.max, currencySymbol) }}</span>
          </div>
          <div class="kyc-limit-bar">
            <div class="kyc-limit-bar__fill" :style="{ width: pct(limits.weekly.used, limits.weekly.max) + '%' }" />
          </div>
        </div>
        <div class="kyc-limit-item">
          <div class="kyc-limit-item__header">
            <span>Mensual</span>
            <span>{{ fmt(limits.monthly.used, currencySymbol) }} / {{ fmt(limits.monthly.max, currencySymbol) }}</span>
          </div>
          <div class="kyc-limit-bar">
            <div class="kyc-limit-bar__fill" :style="{ width: pct(limits.monthly.used, limits.monthly.max) + '%' }" />
          </div>
        </div>
      </div>
      <p class="kyc-limits__hint">
        <i class="fas fa-info-circle" />
        Máx. por transacción: {{ fmt(limits.per_tx.max, currencySymbol) }}
        · Mín.: {{ fmt(limits.per_tx.min, currencySymbol) }}
      </p>
    </div>

  </div>
</template>

<style scoped>
.kyc-banner-wrap  { display:flex;flex-direction:column;gap:0.75rem; }

/* Status bar */
.kyc-status-bar   { display:flex;align-items:center;justify-content:space-between;gap:0.75rem;padding:0.75rem 1rem;border-radius:12px;font-size:0.82rem; }
.kyc-status-bar--none     { background:#f8fafc;border:1.5px solid #e2e8f0; }
.kyc-status-bar--pending  { background:#fffbeb;border:1.5px solid #fde68a; }
.kyc-status-bar--verified { background:#f0fdf4;border:1.5px solid #bbf7d0; }
.kyc-status-bar--rejected { background:#fef2f2;border:1.5px solid #fecaca; }

.kyc-status-bar__left  { display:flex;align-items:center;gap:0.6rem; }
.kyc-status-bar__icon  { font-size:1.1rem; }
.kyc-status-bar--none     .kyc-status-bar__icon { color:#94a3b8; }
.kyc-status-bar--pending  .kyc-status-bar__icon { color:#d97706; }
.kyc-status-bar--verified .kyc-status-bar__icon { color:#16a34a; }
.kyc-status-bar--rejected .kyc-status-bar__icon { color:#dc2626; }

.kyc-status-bar__label  { margin:0;font-weight:600;color:#1e293b; }
.kyc-status-bar__reason { margin:0;font-size:0.75rem;color:#dc2626;margin-top:0.2rem; }

.kyc-status-bar__btn {
  display:inline-flex;align-items:center;gap:0.4rem;padding:6px 14px;
  border-radius:8px;border:none;cursor:pointer;font-size:0.78rem;font-weight:600;
  background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;white-space:nowrap;
  transition:all 0.15s;flex-shrink:0;
}
.kyc-status-bar__btn:hover { filter:brightness(1.08); }

/* Limits */
.kyc-limits       { background:#f8fafc;border-radius:12px;padding:0.9rem 1rem;border:1.5px solid #e2e8f0; }
.kyc-limits__title{ font-size:0.8rem;font-weight:700;color:#475569;margin:0 0 0.75rem;display:flex;align-items:center;gap:0.4rem; }
.kyc-limits__grid { display:flex;flex-direction:column;gap:0.6rem; }
.kyc-limit-item   { display:flex;flex-direction:column;gap:0.25rem; }
.kyc-limit-item__header { display:flex;justify-content:space-between;font-size:0.75rem;color:#64748b; }
.kyc-limit-bar    { height:5px;background:#e2e8f0;border-radius:99px;overflow:hidden; }
.kyc-limit-bar__fill { height:100%;background:linear-gradient(90deg,#667eea,#764ba2);border-radius:99px;transition:width 0.4s ease; }
.kyc-limits__hint { font-size:0.72rem;color:#94a3b8;margin:0.6rem 0 0;display:flex;align-items:center;gap:0.3rem; }
</style>
