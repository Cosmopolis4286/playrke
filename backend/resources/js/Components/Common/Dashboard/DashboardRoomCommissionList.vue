<script setup lang="ts">
import type { SellerRoomCommission } from '@/js/types/roles/seller/dashboard/dashboard-seller'
import DashboardEmptyState from '@/js/Components/Common/Dashboard/DashboardEmptyState.vue'

defineProps<{
  rows: SellerRoomCommission[]
}>()
</script>

<template>
  <div v-if="rows.length" class="rcl-list">
    <div v-for="row in rows" :key="row.room_id" class="rcl-item">
      <span class="rcl-item__room">{{ row.room_name }}</span>
      <span class="rcl-item__commission">{{ row.commission }}%</span>
    </div>
  </div>
  <DashboardEmptyState
    v-else
    icon="fas fa-percentage"
    title="Sin comisiones especiales configuradas"
    description="Usas la tasa general para todas las salas."
    compact
  />
</template>

<style scoped>
.rcl-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.rcl-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.6rem 0.875rem;
  background: var(--ds-surface-2);
  border-radius: 8px;
  font-size: 0.85rem;
}

.rcl-item__room {
  color: var(--ds-text);
  font-family: monospace;
  font-size: 0.8rem;
}

.rcl-item__commission {
  font-weight: 700;
  color: var(--ds-text);
  background: var(--ds-surface);
  padding: 2px 10px;
  border-radius: 20px;
  font-size: 0.78rem;
}
</style>
