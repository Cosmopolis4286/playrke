<script setup lang="ts">
import type { RkeTotals } from '@/js/types/roles/admin/dashboard/dashboard-admin'
import DashboardEmptyState from '@/js/Components/Common/Dashboard/DashboardEmptyState.vue'

defineProps<{
  totals: RkeTotals | null
  formatUsd: (v: number, decimals?: number) => string
}>()
</script>

<template>
  <div v-if="totals && totals.room_count > 0" class="rke-totals">
    <div class="rke-totals__item">
      <span class="rke-totals__label">Total RKE cobrado</span>
      <span class="rke-totals__value rke-totals__value--accent">$ {{ formatUsd(totals.total_rke) }}</span>
    </div>
    <div class="rke-totals__item">
      <span class="rke-totals__label">Total ventas</span>
      <span class="rke-totals__value">$ {{ formatUsd(totals.total_sales) }}</span>
    </div>
    <div class="rke-totals__item">
      <span class="rke-totals__label">Salas con ventas</span>
      <span class="rke-totals__value">{{ totals.room_count }}</span>
    </div>
  </div>
  <DashboardEmptyState
    v-else
    icon="fas fa-percentage"
    title="Sin comisiones registradas"
    description="Aparecerán aquí en cuanto se vendan cartones en alguna sala."
  />
</template>

<style scoped>
.rke-totals {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
}

@media (max-width: 600px) {
  .rke-totals { grid-template-columns: 1fr; }
}

.rke-totals__item {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  padding: 0.875rem 1rem;
  background: var(--ds-surface-2);
  border-radius: 10px;
}

.rke-totals__label {
  font-size: 0.73rem;
  color: var(--ds-text-2);
  font-weight: 500;
}

.rke-totals__value {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--ds-text);
}

.rke-totals__value--accent { color: #a78bfa; }
</style>
