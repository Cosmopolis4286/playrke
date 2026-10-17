<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'
import type { SellerClosingRoom } from '@/js/types/roles/seller/dashboard/dashboard-seller'
import DashboardEmptyState from '@/js/Components/Common/Dashboard/DashboardEmptyState.vue'

defineProps<{
  rooms: SellerClosingRoom[]
}>()

// Reloj de 30 s: basta para la cuenta regresiva en minutos sin recargar.
const now = ref(Date.now())
let timer: ReturnType<typeof setInterval> | undefined
onMounted(() => { timer = setInterval(() => { now.value = Date.now() }, 30_000) })
onBeforeUnmount(() => { if (timer) clearInterval(timer) })

function startTime(room: SellerClosingRoom): string {
  if (room.is_manual || !room.starts_at) return 'Manual'
  const start = new Date(room.starts_at)
  const time  = start.toLocaleTimeString('es', { hour: '2-digit', minute: '2-digit' })
  // La ventana es de 24 h: marca las de mañana para no confundirlas con hoy.
  return start.toDateString() === new Date(now.value).toDateString() ? time : `Mañana ${time}`
}

function closesIn(room: SellerClosingRoom): string | null {
  if (!room.sales_close_at) return null
  const minutes = Math.floor((new Date(room.sales_close_at).getTime() - now.value) / 60_000)
  if (minutes <= 0) return 'Venta cerrada'
  if (minutes < 60) return `Cierra en ${minutes} min`
  return `Cierra en ${Math.floor(minutes / 60)} h ${minutes % 60} min`
}

function isClosingSoon(room: SellerClosingRoom): boolean {
  if (!room.sales_close_at) return false
  return new Date(room.sales_close_at).getTime() - now.value < 30 * 60_000
}
</script>

<template>
  <div v-if="rooms.length" class="sur-list">
    <div v-for="room in rooms" :key="room.id" class="sur-item">
      <div class="sur-item__time">{{ startTime(room) }}</div>
      <div class="sur-item__body">
        <span class="sur-item__name">
          {{ room.uuid_code }}
          <span class="sur-item__tag">{{ room.is_own ? 'Tu comercio' : 'Pública' }}</span>
        </span>
        <span class="sur-item__meta">
          Cartón US$ {{ room.card_price_usd.toFixed(2) }} · Bote US$ {{ room.prize_pool_usd.toFixed(2) }} · {{ room.total_players }} jugadores
        </span>
        <span
          v-if="closesIn(room)"
          class="sur-item__close"
          :class="{ 'sur-item__close--soon': isClosingSoon(room) }"
        >
          <i class="fas fa-clock"></i> {{ closesIn(room) }}
        </span>
      </div>
      <a href="/bingo-card/generate" class="sur-item__cta">Vender</a>
    </div>
  </div>
  <DashboardEmptyState
    v-else
    icon="fas fa-door-closed"
    title="No hay salas por cerrar venta"
    description="Cuando haya salas con cierre en las próximas 24 horas, aparecerán aquí."
    compact
  />
</template>

<style scoped>
.sur-list { display: flex; flex-direction: column; gap: 0.5rem; }

.sur-item {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0.6rem 0.875rem;
  background: var(--ds-surface-2);
  border-radius: 10px;
}

.sur-item__time {
  min-width: 64px; text-align: center; flex-shrink: 0;
  font-size: 0.78rem; font-weight: 800; color: var(--ds-text);
  background: var(--ds-surface); border-radius: 8px; padding: 6px 4px;
}

.sur-item__body { display: flex; flex-direction: column; gap: 2px; min-width: 0; flex: 1; }
.sur-item__name { font-family: monospace; font-size: 0.8rem; color: var(--ds-text); display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.sur-item__tag {
  font-family: inherit; font-size: 0.62rem; font-weight: 600;
  padding: 1px 7px; border-radius: 20px;
  background: var(--ds-badge-bg); color: var(--ds-badge-color);
}
.sur-item__meta { font-size: 0.72rem; color: var(--ds-text-muted); }
.sur-item__close { font-size: 0.72rem; font-weight: 600; color: var(--ds-text-2); }
.sur-item__close--soon { color: #dc2626; }

.sur-item__cta {
  flex-shrink: 0; font-size: 0.75rem; font-weight: 700;
  padding: 5px 12px; border-radius: 8px;
  background: #db2777; color: #fff; text-decoration: none;
}
.sur-item__cta:hover { background: #be185d; color: #fff; text-decoration: none; }
</style>
