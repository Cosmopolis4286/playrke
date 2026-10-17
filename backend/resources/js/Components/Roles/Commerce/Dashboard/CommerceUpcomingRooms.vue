<script setup lang="ts">
import type { CommerceUpcomingRoom } from '@/js/types/roles/commerce/dashboard/dashboard-commerce'
import DashboardEmptyState from '@/js/Components/Common/Dashboard/DashboardEmptyState.vue'

defineProps<{
  rooms: CommerceUpcomingRoom[]
}>()

function startLabel(room: CommerceUpcomingRoom): string {
  if (room.is_manual || !room.starts_at) return 'Manual'
  const start = new Date(room.starts_at)
  const time  = start.toLocaleTimeString('es', { hour: '2-digit', minute: '2-digit' })
  return start.toDateString() === new Date().toDateString() ? time : `Mañana ${time}`
}

/** Ocupación respecto al cupo de jugadores (si la sala tiene tope). */
function occupancy(room: CommerceUpcomingRoom): number | null {
  if (!room.max_players) return null
  return Math.min(100, Math.round((room.total_players / room.max_players) * 100))
}
</script>

<template>
  <div v-if="rooms.length" class="cur-list">
    <a v-for="room in rooms" :key="room.id" href="/bingo-rooms" class="cur-item">
      <div class="cur-item__time">{{ startLabel(room) }}</div>
      <div class="cur-item__body">
        <span class="cur-item__name">
          {{ room.uuid_code }}
          <span v-if="!room.sales_open" class="cur-item__tag">Venta cerrada</span>
        </span>
        <span class="cur-item__meta">
          {{ room.total_cards }} cartones · {{ room.total_players }}<template v-if="room.max_players">/{{ room.max_players }}</template> jugadores · Bote US$ {{ room.prize_pool_usd.toFixed(2) }}
        </span>
        <span v-if="occupancy(room) !== null" class="cur-item__bar">
          <span class="cur-item__bar-fill" :style="{ width: `${occupancy(room)}%` }"></span>
        </span>
      </div>
    </a>
  </div>
  <DashboardEmptyState
    v-else
    icon="fas fa-calendar-plus"
    title="No tienes salas para hoy ni mañana"
    description="Programa una sala para que tu red pueda vender."
    compact
  />
</template>

<style scoped>
.cur-list { display: flex; flex-direction: column; gap: 0.5rem; }

.cur-item {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0.6rem 0.875rem; border-radius: 10px;
  background: var(--ds-surface-2);
  text-decoration: none; color: inherit; transition: box-shadow 0.15s;
}
.cur-item:hover { box-shadow: var(--ds-card-hover-shadow); text-decoration: none; }

.cur-item__time {
  min-width: 64px; text-align: center; flex-shrink: 0;
  font-size: 0.78rem; font-weight: 800; color: var(--ds-text);
  background: var(--ds-surface); border-radius: 8px; padding: 6px 4px;
}

.cur-item__body { display: flex; flex-direction: column; gap: 3px; min-width: 0; flex: 1; }
.cur-item__name { font-family: monospace; font-size: 0.8rem; color: var(--ds-text); display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.cur-item__tag {
  font-family: inherit; font-size: 0.62rem; font-weight: 600;
  padding: 1px 7px; border-radius: 20px;
  background: var(--ds-badge-bg); color: var(--ds-badge-color);
}
.cur-item__meta { font-size: 0.72rem; color: var(--ds-text-muted); }
.cur-item__bar { height: 4px; border-radius: 4px; background: var(--ds-surface); overflow: hidden; }
.cur-item__bar-fill { display: block; height: 100%; background: #db2777; }
</style>
