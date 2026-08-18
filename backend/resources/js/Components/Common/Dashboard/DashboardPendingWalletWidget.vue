<script setup lang="ts" name="DashboardPendingWalletWidget">
  import DashboardSection      from '@/js/Components/Common/Dashboard/DashboardSection.vue'
  import DashboardActivityFeed from '@/js/Components/Common/Dashboard/DashboardActivityFeed.vue'
  import type { ActivityItem } from '@/js/types/common/dashboard/dashboard'

  withDefaults(defineProps<{
    pendingCount: number
    isLive?: boolean
    recentMovements?: ActivityItem[]
    loading?: boolean
  }>(), {
    isLive: false,
    recentMovements: () => [],
    loading: false,
  })
</script>

<template>
  <DashboardSection
    title="Movimientos pendientes"
    icon="fas fa-wallet"
    subtitle="Depósitos y retiros por aprobar"
    :loading="loading"
    skeleton-variant="list"
  >
    <template #actions>
      <a href="/wallet-movements" class="pw-link">Ver todos</a>
    </template>

    <div class="pw-summary">
      <span class="pw-summary__count" :class="{ 'pw-summary__count--empty': pendingCount === 0 }">
        {{ pendingCount }}
      </span>
      <span class="pw-summary__label">
        {{ pendingCount === 1 ? 'pendiente' : 'pendientes' }}
        <span v-if="isLive" class="pw-live-dot" title="Escuchando en vivo"></span>
      </span>
    </div>

    <DashboardActivityFeed :items="recentMovements" :maxItems="5" />
  </DashboardSection>
</template>

<style scoped>
.pw-link {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--ds-text-2);
  text-decoration: none;
}
.pw-link:hover { color: var(--ds-text); text-decoration: underline; }

.pw-summary {
  display: flex;
  align-items: baseline;
  gap: 0.4rem;
  margin-bottom: 0.9rem;
}
.pw-summary__count {
  font-size: 1.75rem;
  font-weight: 700;
  color: #f59e0b;
  line-height: 1;
}
.pw-summary__count--empty { color: var(--ds-text-2); }
.pw-summary__label {
  font-size: 0.78rem;
  color: var(--ds-text-2);
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.pw-live-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: #22c55e; box-shadow: 0 0 5px #22c55e;
  animation: pwLivePulse 1.8s ease-in-out infinite;
  display: inline-block;
}
@keyframes pwLivePulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50%       { opacity: 0.5; transform: scale(0.8); }
}
</style>
