<script setup lang="ts">
import type { PrizeWonRow } from '@/js/types/roles/player/dashboard/dashboard-player'
import DashboardEmptyState from '@/js/Components/Common/Dashboard/DashboardEmptyState.vue'

defineProps<{
  prizes: PrizeWonRow[]
  formatUsd: (v: number, decimals?: number) => string
}>()

const statusClass: Record<string, string> = {
  waiting: 'pl-badge--waiting',
  processing: 'pl-badge--processing',
  claimed: 'pl-badge--claimed',
  cancelled: 'pl-badge--cancelled',
  suspended: 'pl-badge--suspended',
}
</script>

<template>
  <div v-if="prizes.length" class="pl-list">
    <div v-for="prize in prizes" :key="prize.id" class="pl-item">
      <div class="pl-item__icon"><i class="fas fa-trophy"></i></div>
      <div class="pl-item__main">
        <span class="pl-item__title">{{ prize.prize_label }} · {{ prize.room_name }}</span>
        <span class="pl-item__amount">$ {{ formatUsd(prize.amount_usd) }}</span>
      </div>
      <span class="pl-badge" :class="statusClass[prize.status] ?? 'pl-badge--other'">{{ prize.status_label }}</span>
    </div>
  </div>
  <DashboardEmptyState
    v-else
    icon="fas fa-trophy"
    title="Sin premios ganados todavía"
    description="Tus victorias aparecerán aquí con el detalle del premio."
  />
</template>

<style scoped>
.pl-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.pl-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.6rem 0.875rem;
  background: var(--ds-surface-2);
  border-radius: 8px;
}

.pl-item__icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: rgba(245, 158, 11, 0.15);
  color: #f59e0b;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 0.85rem;
}

.pl-item__main {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.pl-item__title {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--ds-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.pl-item__amount {
  font-size: 0.78rem;
  color: var(--ds-text-2);
}

.pl-badge {
  font-size: 0.7rem;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 20px;
  white-space: nowrap;
}
.pl-badge--waiting    { background: rgba(37,99,235,.15);  color: #60a5fa; }
.pl-badge--processing { background: rgba(245,158,11,.15); color: #fbbf24; }
.pl-badge--claimed    { background: rgba(22,163,74,.15);  color: #4ade80; }
.pl-badge--cancelled  { background: rgba(239,68,68,.15);  color: #f87171; }
.pl-badge--suspended  { background: rgba(239,68,68,.15);  color: #f87171; }
.pl-badge--other      { background: var(--ds-surface);    color: var(--ds-text-2); }
</style>
