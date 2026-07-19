<script setup lang="ts">
import { ref, computed } from 'vue'
import type { WalletMovement } from '@js/types/wallet'

const props = defineProps<{
  movements: WalletMovement[]
  currencySymbol: string | undefined
  decimalPlaces: number | undefined
}>()

const summaryPeriodOptions = [
  { value: '24h',  label: '24 horas' },
  { value: '7d',   label: '7 días' },
  { value: '15d',  label: '15 días' },
  { value: '30d',  label: '30 días' },
] as const

type SummaryPeriod = typeof summaryPeriodOptions[number]['value']

const summaryPeriod = ref<SummaryPeriod>('24h')

const summaryPeriodHours: Record<SummaryPeriod, number | null> = {
  '24h': 24,
  '7d':  24 * 7,
  '15d': 24 * 15,
  '30d': 24 * 30,
}

const summaryMovements = computed(() => {
  const hours = summaryPeriodHours[summaryPeriod.value]
  if (hours === null) return props.movements

  const threshold = Date.now() - hours * 3600000
  return props.movements.filter(m => new Date(m.created_at).getTime() >= threshold)
})

const totalDeposits = computed(() =>
  summaryMovements.value.filter(m => m.type === 'DEPOSIT').reduce((s, m) => s + m.amount, 0)
)
const totalPrizes = computed(() =>
  summaryMovements.value.filter(m => m.type === 'PRIZE').reduce((s, m) => s + m.amount, 0)
)
const totalBonusPrizes = computed(() =>
  summaryMovements.value.filter(m => m.type === 'BONUS_PRIZE').reduce((s, m) => s + m.amount, 0)
)
const totalWithdrawals = computed(() =>
  summaryMovements.value.filter(m => m.type === 'WITHDRAWAL').reduce((s, m) => s + m.amount, 0)
)

function formatAmount(value: number) {
  return new Intl.NumberFormat('es-VE', {
    minimumFractionDigits: props.decimalPlaces ?? 2,
    maximumFractionDigits: props.decimalPlaces ?? 2,
  }).format(value)
}
</script>

<template>
  <div class="wallet-card">
    <div class="summary-header">
      <h3 class="wallet-card__title">
        <i class="fas fa-chart-line"></i> Resumen de actividad
        <span class="live-dot" title="Actualización en tiempo real"></span>
      </h3>
      <select v-model="summaryPeriod" class="summary-period-select">
        <option v-for="opt in summaryPeriodOptions" :key="opt.value" :value="opt.value">
          {{ opt.label }}
        </option>
      </select>
    </div>
    <div class="summary-list">
      <div class="summary-item summary-item--deposit">
        <div class="summary-item__icon"><i class="fas fa-arrow-down"></i></div>
        <div class="summary-item__info">
          <p class="summary-item__label">Depósitos</p>
          <p class="summary-item__sublabel">Total recibido</p>
        </div>
        <p class="summary-item__amount summary-item__amount--deposit">
          {{ currencySymbol }} {{ formatAmount(totalDeposits) }}
        </p>
      </div>

      <div class="summary-item summary-item--prize">
        <div class="summary-item__icon"><i class="fas fa-trophy"></i></div>
        <div class="summary-item__info">
          <p class="summary-item__label">Premios</p>
          <p class="summary-item__sublabel">Ganancias</p>
        </div>
        <p class="summary-item__amount summary-item__amount--prize">
          {{ currencySymbol }} {{ formatAmount(totalPrizes) }}
        </p>
      </div>

      <div class="summary-item summary-item--withdrawal">
        <div class="summary-item__icon"><i class="fas fa-arrow-up"></i></div>
        <div class="summary-item__info">
          <p class="summary-item__label">Retiros</p>
          <p class="summary-item__sublabel">Total retirado</p>
        </div>
        <p class="summary-item__amount summary-item__amount--withdrawal">
          {{ currencySymbol }} {{ formatAmount(totalWithdrawals) }}
        </p>
      </div>

      <div v-if="totalBonusPrizes > 0" class="summary-item summary-item--bonus">
        <div class="summary-item__icon"><i class="fas fa-gift"></i></div>
        <div class="summary-item__info">
          <p class="summary-item__label">Premios bono</p>
          <p class="summary-item__sublabel">No retirable, solo para jugar</p>
        </div>
        <p class="summary-item__amount summary-item__amount--bonus">
          {{ currencySymbol }} {{ formatAmount(totalBonusPrizes) }}
        </p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.wallet-card {
  background: var(--ds-surface);
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.wallet-card__title {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--ds-text-2);
  margin: 0 0 1rem;
  display: flex;
  align-items: center;
  gap: 8px;
}

.wallet-card__title i { color: #667eea; }

.live-dot {
  display: inline-block;
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #22c55e;
  margin-left: 6px;
  vertical-align: middle;
  animation: live-pulse 2s ease-in-out infinite;
}

@keyframes live-pulse {
  0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.5); }
  50%       { opacity: 0.7; box-shadow: 0 0 0 4px rgba(34, 197, 94, 0); }
}

/* ── Resumen ── */
.summary-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 1rem;
}
.summary-header .wallet-card__title { margin: 0; }

.summary-period-select {
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--ds-text-2);
  background: var(--ds-surface-2);
  border: 1px solid var(--ds-border);
  border-radius: 8px;
  padding: 5px 10px;
  cursor: pointer;
  outline: none;
  transition: border-color 0.15s;
}
.summary-period-select:hover,
.summary-period-select:focus { border-color: #667eea; }

.summary-list { display: flex; flex-direction: column; gap: 10px; }

.summary-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  border-radius: 10px;
}

.summary-item--deposit    { background: rgba(34, 197, 94, .12); }
.summary-item--prize      { background: rgba(245, 158, 11, .12); }
.summary-item--withdrawal { background: rgba(59, 130, 246, .12); }
.summary-item--bonus      { background: rgba(168, 85, 247, .12); }

.summary-item__icon {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
  color: #fff;
  flex-shrink: 0;
}

.summary-item--deposit    .summary-item__icon { background: #22c55e; }
.summary-item--prize      .summary-item__icon { background: #f59e0b; }
.summary-item--withdrawal .summary-item__icon { background: #3b82f6; }
.summary-item--bonus      .summary-item__icon { background: #a855f7; }

.summary-item__info { flex: 1; min-width: 0; }

.summary-item__label {
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--ds-text-2);
  margin: 0;
}

.summary-item__sublabel {
  font-size: 0.72rem;
  color: var(--ds-text-muted);
  margin: 0;
}

.summary-item__amount {
  font-size: 0.95rem;
  font-weight: 700;
  margin: 0;
  white-space: nowrap;
}

.summary-item__amount--deposit    { color: #16a34a; }
.summary-item__amount--prize      { color: #d97706; }
.summary-item__amount--withdrawal { color: #2563eb; }
.summary-item__amount--bonus      { color: #9333ea; }
</style>
